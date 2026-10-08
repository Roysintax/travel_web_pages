<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdminResourceRequest;
use App\Models\MediaAsset;
use App\Services\AdminResourceRegistry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminResourceController extends Controller
{
    public function __construct(private AdminResourceRegistry $registry) {}

    public function index(Request $request, string $resource): View
    {
        $definition = $this->registry->get($resource);
        $query = $definition['model']::query();
        $statusField = isset($definition['fields']['status']) ? 'status' : (isset($definition['fields']['preview_status']) ? 'preview_status' : null);
        $statusOptions = $statusField ? $definition['fields'][$statusField]['options'] : [];
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', Rule::in(array_keys($statusOptions))]]);
        $search = $filters['q'] ?? '';
        $status = $filters['status'] ?? '';
        if ($statusField && $status !== '') {
            $query->where($statusField, $status);
        }
        if ($search !== '') {
            $query->where($definition['title'], 'like', '%'.$search.'%');
        }
        $records = $query->orderByDesc((new $definition['model'])->getKeyName())->paginate(12)->withQueryString();

        $relationLabels = [];
        foreach ($definition['columns'] as $column) {
            $field = $definition['fields'][$column] ?? null;
            if ($field && $field['type'] === 'relation') {
                $relationLabels[$column] = $field['model']::whereIn((new $field['model'])->getKeyName(), $records->pluck($column))->pluck($field['column'], (new $field['model'])->getKeyName());
            }
        }

        return view('admin.index', compact('definition', 'resource', 'records', 'search', 'status', 'statusOptions', 'relationLabels'));
    }

    public function create(string $resource): View
    {
        $definition = $this->registry->get($resource);
        abort_unless($definition['mode'] === 'crud', 403);
        $record = new $definition['model'];

        return $this->form($resource, $definition, $record);
    }

    public function edit(string $resource, string $key): View
    {
        $definition = $this->registry->get($resource);
        abort_if($definition['mode'] === 'readonly', 403);

        return $this->form($resource, $definition, $this->registry->record($resource, $key));
    }

    private function form(string $resource, array $definition, Model $record): View
    {
        $options = [];
        foreach ($definition['fields'] as $name => $field) {
            if ($field['type'] === 'relation') {
                $query = $field['model']::query();
                if ($name === 'image_id') {
                    $query->where('media_type', 'image');
                }
                $options[$name] = $query->orderBy($field['column'])->pluck($field['column'], (new $field['model'])->getKeyName());
            }
        }
        $media = $resource === 'pages' ? MediaAsset::orderBy('file_path')->get() : collect();
        $selectedImage = isset($definition['fields']['image_id']) ? MediaAsset::find(old('image_id', $record->getAttribute('image_id'))) : null;

        return view('admin.form', compact('resource', 'definition', 'record', 'options', 'media', 'selectedImage'));
    }

    public function store(AdminResourceRequest $request, string $resource): RedirectResponse
    {
        $definition = $this->registry->get($resource);
        abort_unless($definition['mode'] === 'crud', 403);
        $this->save($request, $resource, new $definition['model']);

        return redirect()->route('admin.resources.index', $resource)->with('success', 'Data berhasil ditambahkan.');
    }

    public function update(AdminResourceRequest $request, string $resource, string $key): RedirectResponse
    {
        abort_if($this->registry->get($resource)['mode'] === 'readonly', 403);
        $this->save($request, $resource, $this->registry->record($resource, $key));

        return redirect()->route('admin.resources.index', $resource)->with('success', 'Perubahan berhasil disimpan.');
    }

    private function save(AdminResourceRequest $request, string $resource, Model $record): void
    {
        $validated = $request->validated();
        $uploadedPaths = [];
        try {
            foreach (['upload', 'image_upload'] as $field) {
                if (isset($validated[$field])) {
                    $uploadedPaths[$field] = $this->storeUpload($validated[$field], $field);
                }
            }
            foreach ($validated['page_images'] ?? [] as $index => $file) {
                $uploadedPaths['page_images.'.$index] = $this->storeUpload($file, 'page_images.'.$index);
            }
            DB::transaction(function () use ($validated, $resource, $record, $uploadedPaths): void {
                $data = $validated;
                $media = $data['media_ids'] ?? [];
                unset($data['media_ids'], $data['upload'], $data['image_upload'], $data['page_images']);
                if (isset($uploadedPaths['upload'])) {
                    $data['file_path'] = 'storage/'.$uploadedPaths['upload'];
                }
                if (isset($uploadedPaths['image_upload'])) {
                    $image = $this->createImage($uploadedPaths['image_upload'], $data['title'] ?? $data['name'] ?? '');
                    $data['image_id'] = $image->id;
                }
                if ($resource === 'pages') {
                    foreach ($uploadedPaths as $field => $path) {
                        if (str_starts_with($field, 'page_images.')) {
                            $media[] = $this->createImage($path, $data['title'])->id;
                        }
                    }
                }
                if ($resource === 'bookings') {
                    $data['reviewed_at'] = $data['preview_status'] === 'draft' ? null : ($record->reviewed_at ?? now());
                }
                $record->fill($data)->save();
                if ($resource === 'pages') {
                    $existing = $record->media()->get()->keyBy('id');
                    $pivots = [];
                    foreach ($media as $id) {
                        $pivots[$id] = ['placement' => $existing->get($id)?->pivot->placement ?? 'content', 'sort_order' => $existing->get($id)?->pivot->sort_order ?? 0];
                    }
                    $record->media()->sync($pivots);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }
            throw $exception;
        }
        if ($resource === 'settings') {
            Cache::forget('site_settings_all');
        }
    }

    private function storeUpload(UploadedFile $file, string $field): string
    {
        $path = $file->store('cms-media', 'public');
        if (! $path) {
            throw ValidationException::withMessages([$field => 'File gagal disimpan. Silakan coba kembali.']);
        }

        return $path;
    }

    private function createImage(string $path, string $altText): MediaAsset
    {
        return MediaAsset::create(['file_path' => 'storage/'.$path, 'media_type' => 'image', 'alt_text' => $altText]);
    }

    public function show(string $resource, string $key): View
    {
        $definition = $this->registry->get($resource);
        $record = $this->registry->record($resource, $key);
        if ($resource === 'chats') {
            $record->load('messages');
        }

        return view('admin.show', compact('resource', 'definition', 'record'));
    }

    public function destroy(string $resource, string $key): RedirectResponse
    {
        abort_unless($this->registry->get($resource)['mode'] === 'crud', 403);
        $record = $this->registry->record($resource, $key);
        try {
            $record->delete();
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23503'], true)) {
                return back()->with('error', 'Data masih digunakan. Lepaskan relasinya sebelum menghapus.');
            }
            throw $exception;
        }
        if ($resource === 'settings') {
            Cache::forget('site_settings_all');
        }

        return redirect()->route('admin.resources.index', $resource)->with('success', 'Data berhasil dihapus.');
    }
}
