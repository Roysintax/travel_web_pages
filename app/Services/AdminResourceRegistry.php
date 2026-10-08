<?php

namespace App\Services;

use App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

class AdminResourceRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $text = fn (string $label, int $max = 255, bool $nullable = false): array => ['label' => $label, 'type' => 'text', 'rules' => [$nullable ? 'nullable' : 'required', 'string', 'max:'.$max]];
        $long = fn (string $label, int $max = 10000, bool $nullable = false): array => ['label' => $label, 'type' => 'textarea', 'rules' => [$nullable ? 'nullable' : 'required', 'string', 'max:'.$max]];
        $select = fn (string $label, array $options): array => ['label' => $label, 'type' => 'select', 'options' => $options, 'rules' => ['required', Rule::in(array_keys($options))]];
        $relation = fn (string $label, string $model, string $column, bool $nullable = false): array => ['label' => $label, 'type' => 'relation', 'model' => $model, 'column' => $column, 'rules' => [$nullable ? 'nullable' : 'required', 'integer', 'exists:'.(new $model)->getTable().','.(new $model)->getKeyName()]];
        $number = fn (string $label, string $rule = 'integer', int $min = 0, int $max = 65535, bool $nullable = false): array => ['label' => $label, 'type' => 'number', 'default' => ($nullable ? '' : $min), 'rules' => [$nullable ? 'nullable' : 'required', $rule, 'min:'.$min, 'max:'.$max]];
        $unique = fn (array $field): array => $field + ['unique' => true];
        $flag = fn (string $label): array => ['label' => $label, 'type' => 'checkbox', 'rules' => ['required', 'boolean']];
        $package = $relation('Paket', Models\Package::class, 'title');
        $page = $relation('Halaman', Models\Page::class, 'title');
        $image = $relation('Gambar', Models\MediaAsset::class, 'file_path', true);
        $currency = $text('Mata uang', 3) + ['default' => 'IDR'];
        $slug = $unique($text('Slug', 100));
        $slug['rules'][] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
        $resources = [
            'packages' => ['model' => Models\Package::class, 'label' => 'Paket perjalanan', 'group' => 'Travel Catalog', 'title' => 'title', 'columns' => ['title', 'category', 'price_per_person', 'is_active'], 'fields' => ['title' => $text('Nama paket', 180), 'slug' => $slug, 'booking_key' => $unique($text('Booking key', 40, true)), 'destination_id' => $relation('Destinasi', Models\Destination::class, 'name'), 'image_id' => $image, 'description' => $long('Deskripsi'), 'category' => $text('Kategori', 40), 'badge' => $text('Badge', 80, true), 'days' => $number('Hari', 'integer', 1), 'nights' => $number('Malam'), 'price_per_person' => $number('Harga per orang', 'numeric', 0, 9999999999999), 'original_price' => $number('Harga awal', 'numeric', 0, 9999999999999, true), 'currency' => $currency, 'rating' => $number('Rating', 'numeric', 0, 5, true), 'review_count' => $number('Jumlah ulasan', 'integer', 0, 4294967295), 'is_luxury' => $flag('Paket luxury'), 'is_active' => $flag('Aktif') + ['default' => 1]]],
            'destinations' => ['model' => Models\Destination::class, 'label' => 'Destinasi', 'group' => 'Travel Catalog', 'title' => 'name', 'columns' => ['name', 'region', 'slug'], 'fields' => ['name' => $text('Nama', 150), 'slug' => $slug, 'region' => $text('Wilayah', 80), 'image_id' => $image]],
            'package-items' => ['model' => Models\PackageItem::class, 'label' => 'Fasilitas paket', 'group' => 'Travel Catalog', 'title' => 'description', 'columns' => ['package_id', 'item_type', 'description'], 'fields' => ['package_id' => $package, 'item_type' => $select('Jenis', ['feature' => 'Feature', 'included' => 'Included']), 'description' => $long('Deskripsi'), 'sort_order' => $number('Urutan')]],
            'itineraries' => ['model' => Models\PackageItinerary::class, 'label' => 'Itinerary', 'group' => 'Travel Catalog', 'title' => 'title', 'columns' => ['package_id', 'day_number', 'title'], 'fields' => ['package_id' => $package, 'day_number' => $number('Hari ke', 'integer', 1), 'title' => $text('Judul'), 'description' => $long('Deskripsi')]],
            'accommodations' => ['model' => Models\PackageAccommodation::class, 'label' => 'Akomodasi', 'group' => 'Travel Catalog', 'title' => 'name', 'columns' => ['package_id', 'name', 'star_label'], 'fields' => ['package_id' => $unique($package), 'name' => $text('Nama hotel'), 'star_label' => $text('Label bintang', 80, true), 'perks' => $long('Fasilitas', 10000, true)]],
            'pricing-plans' => ['model' => Models\PricingPlan::class, 'label' => 'Pricing plans', 'group' => 'Travel Catalog', 'title' => 'name', 'columns' => ['name', 'price_per_person', 'currency'], 'fields' => ['name' => $unique($text('Nama', 100)), 'price_per_person' => $number('Harga', 'numeric', 0, 9999999999999), 'currency' => $currency]],
            'pages' => ['model' => Models\Page::class, 'label' => 'Halaman', 'group' => 'Content', 'title' => 'title', 'columns' => ['title', 'slug', 'html_file'], 'fields' => ['title' => $text('Judul'), 'slug' => $unique($text('Slug', 80)), 'html_file' => $unique($text('File halaman', 100)), 'meta_description' => $long('Meta description', 10000, true)]],
            'sections' => ['model' => Models\PageSection::class, 'label' => 'Bagian halaman', 'group' => 'Content', 'title' => 'section_key', 'columns' => ['page_id', 'section_key', 'heading'], 'fields' => ['page_id' => $page, 'section_key' => $text('Section key', 100), 'heading' => $text('Heading', 500, true)]],
            'faqs' => ['model' => Models\Faq::class, 'label' => 'FAQ', 'group' => 'Content', 'title' => 'question', 'columns' => ['question', 'page_id', 'sort_order'], 'fields' => ['page_id' => $page, 'question' => $text('Pertanyaan', 500), 'answer' => $long('Jawaban'), 'sort_order' => $number('Urutan')]],
            'media' => ['model' => Models\MediaAsset::class, 'label' => 'Media library', 'group' => 'Content', 'title' => 'file_path', 'columns' => ['file_path', 'media_type', 'alt_text'], 'fields' => ['file_path' => $unique($text('Path aset', 255)), 'media_type' => $select('Jenis', ['image' => 'Image', 'video' => 'Video']), 'alt_text' => $text('Teks alternatif', 500, true)]],
            'settings' => ['model' => Models\SiteSetting::class, 'label' => 'Pengaturan situs', 'group' => 'Content', 'title' => 'setting_key', 'columns' => ['setting_key', 'setting_value', 'description'], 'fields' => ['setting_key' => $unique($text('Setting key', 100)), 'setting_value' => $long('Nilai'), 'description' => $text('Deskripsi', 255, true)]],
            'bookings' => ['model' => Models\Booking::class, 'label' => 'Booking preview', 'group' => 'Operations', 'title' => 'reference_code', 'columns' => ['reference_code', 'lead_name', 'departure_date', 'estimated_total', 'preview_status'], 'mode' => 'status', 'fields' => ['preview_status' => $select('Status', ['draft' => 'Draft', 'reviewed' => 'Reviewed', 'ready' => 'Ready'])]],
            'contacts' => ['model' => Models\ContactMessage::class, 'label' => 'Pesan kontak', 'group' => 'Operations', 'title' => 'full_name', 'columns' => ['full_name', 'email', 'topic', 'status'], 'mode' => 'status', 'fields' => ['status' => $select('Status', ['new' => 'New', 'in_progress' => 'In progress', 'resolved' => 'Resolved'])]],
            'chats' => ['model' => Models\ChatSession::class, 'label' => 'Percakapan AI', 'group' => 'Operations', 'title' => 'model', 'columns' => ['id', 'model', 'created_at', 'expires_at'], 'mode' => 'readonly', 'fields' => []],
        ];
        foreach ($resources as &$resource) {
            $resource['mode'] ??= 'crud';
        }

        return $resources;
    }

    /** @return array<string, mixed> */
    public function get(string $resource): array
    {
        return $this->all()[$resource] ?? abort(404);
    }

    public function record(string $resource, string $key): Model
    {
        $definition = $this->get($resource);

        return $definition['model']::query()->findOrFail($key);
    }
}
