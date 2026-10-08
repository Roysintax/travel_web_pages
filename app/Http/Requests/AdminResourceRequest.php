<?php

namespace App\Http\Requests;

use App\Http\Middleware\AdminAuthentication;
use App\Services\AdminResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return AdminAuthentication::allowsLocalAccess($this) || (bool) $this->user()?->is_admin;
    }

    /** @return array<string, mixed> */
    public function rules(AdminResourceRegistry $registry): array
    {
        $resource = (string) $this->route('resource');
        $definition = $registry->get($resource);
        $record = $this->route('key') ? $registry->record($resource, (string) $this->route('key')) : null;
        $rules = [];
        foreach ($definition['fields'] as $name => $field) {
            $rules[$name] = $field['rules'];
            if ($field['unique'] ?? false) {
                $unique = Rule::unique((new $definition['model'])->getTable(), $name);
                if ($record) {
                    $unique->ignore($record);
                }
                $rules[$name][] = $unique;
            }
        }
        if ($record && array_key_exists($record->getKeyName(), $rules)) {
            $rules[$record->getKeyName()][] = Rule::in([(string) $record->getKey()]);
        }
        if (isset($rules['image_id'])) {
            $rules['image_id'] = ['nullable', 'integer', Rule::exists('media_assets', 'id')->where('media_type', 'image')];
            $rules['image_upload'] = ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:2048'];
        }
        if ($resource === 'packages') {
            $rules['nights'][] = 'lt:days';
            $rules['currency'][] = 'regex:/^[A-Z]{3}$/';
        }
        if ($resource === 'pricing-plans') {
            $rules['currency'][] = 'regex:/^[A-Z]{3}$/';
        }
        if ($resource === 'media') {
            $rules['file_path'][0] = 'nullable';
            $rules['file_path'][] = 'required_without:upload';
            $rules['file_path'][] = 'regex:~\A(?:assets/|storage/cms-media/)(?!.*\.\.)[a-zA-Z0-9_./-]+\.(?:png|jpe?g|webp|avif|gif|svg|mp4|webm)\z~';
            $rules['upload'] = ['nullable', 'file', 'max:2048'];
            if ($this->input('media_type') === 'video') {
                $rules['upload'][] = 'mimetypes:video/mp4,video/webm';
            } else {
                $rules['upload'][] = 'image';
                $rules['upload'][] = 'mimes:jpg,jpeg,png,gif,webp,avif';
            }
        }
        if (in_array($resource, ['itineraries', 'sections'], true)) {
            $column = $resource === 'itineraries' ? 'day_number' : 'section_key';
            $parent = $resource === 'itineraries' ? 'package_id' : 'page_id';
            $unique = Rule::unique((new $definition['model'])->getTable(), $column)->where($parent, $this->input($parent));
            if ($record) {
                $unique->ignore($record);
            }
            $rules[$column][] = $unique;
        }
        if ($resource === 'pages') {
            $rules['media_ids'] = ['nullable', 'array'];
            $rules['media_ids.*'] = ['integer', 'distinct', 'exists:media_assets,id'];
            $rules['page_images'] = ['nullable', 'array', 'max:5'];
            $rules['page_images.*'] = ['image', 'mimes:jpg,jpeg,png,gif,webp,avif', 'max:2048'];
        }

        return $rules;
    }
}
