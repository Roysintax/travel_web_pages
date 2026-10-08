<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\ChatSession;
use App\Models\ContactMessage;
use App\Models\Destination;
use App\Models\MediaAsset;
use App\Models\Package;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\AdminResourceRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_destination_upload_creates_media_and_replacement_preserves_shared_image(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->post('/admin/destinations', ['name' => 'Bali', 'slug' => 'bali', 'region' => 'Asia', 'image_upload' => $this->imageUpload()])->assertSessionHasNoErrors();
        $destination = Destination::where('slug', 'bali')->firstOrFail();
        $this->assertNotNull($destination->image_id);
        $firstImage = MediaAsset::findOrFail($destination->image_id);
        Storage::disk('public')->assertExists(substr($firstImage->file_path, 8));
        $this->put('/admin/destinations/'.$destination->id, ['name' => 'Bali', 'slug' => 'bali', 'region' => 'Asia', 'image_upload' => $this->imageUpload()])->assertSessionHasNoErrors();
        $this->assertNotSame($firstImage->id, $destination->fresh()->image_id);
        $this->assertDatabaseHas('media_assets', ['id' => $firstImage->id]);
        Storage::disk('public')->assertExists(substr($firstImage->file_path, 8));
        $this->post('/admin/destinations', ['name' => 'Unsafe', 'slug' => 'unsafe', 'region' => 'Asia', 'image_upload' => UploadedFile::fake()->create('shell.php', 1, 'application/x-httpd-php')])->assertSessionHasErrors('image_upload');
        $this->assertDatabaseMissing('destinations', ['slug' => 'unsafe']);
    }

    public function test_package_and_page_forms_support_image_uploads(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $destination = Destination::create(['name' => 'Bali', 'slug' => 'bali', 'region' => 'Asia']);
        $this->actingAs($admin)->post('/admin/packages', ['title' => 'Bali', 'slug' => 'bali', 'destination_id' => $destination->id, 'description' => 'Holiday', 'category' => 'beach', 'days' => 2, 'nights' => 1, 'price_per_person' => 1000000, 'currency' => 'IDR', 'review_count' => 0, 'is_luxury' => 0, 'is_active' => 1, 'image_upload' => $this->imageUpload()])->assertSessionHasNoErrors();
        $this->assertNotNull(Package::where('slug', 'bali')->firstOrFail()->image_id);
        $this->post('/admin/pages', ['title' => 'About', 'slug' => 'about', 'html_file' => 'about.html', 'page_images' => [$this->imageUpload(), $this->imageUpload()]])->assertSessionHasNoErrors();
        $this->assertCount(2, Page::where('slug', 'about')->firstOrFail()->media);
        $this->get('/admin/packages/create')->assertOk()->assertSee('Upload gambar baru');
        $this->get('/admin/destinations/create')->assertOk()->assertSee('Upload gambar baru');
        $this->get('/admin/pages/create')->assertOk()->assertSee('Upload gambar halaman');
    }

    private function imageUpload(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('image.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRZkAAAAASUVORK5CYII='));
    }

    public function test_disabled_login_allows_guest_dashboard_and_crud(): void
    {
        config(['cms.auth_enabled' => false]);
        $this->app['env'] = 'local';
        $this->get('/admin')->assertOk()->assertSee('Mode tanpa login');
        $this->get('/admin/login')->assertRedirect('/admin');
        $this->withSession(['_token' => 'local-test-token'])->post('/admin/destinations', ['_token' => 'local-test-token', 'name' => 'Bali', 'slug' => 'bali', 'region' => 'Asia'])->assertSessionHasNoErrors()->assertRedirect('/admin/destinations');
        $this->assertDatabaseHas('destinations', ['slug' => 'bali']);
    }

    public function test_disabled_login_still_protects_remote_requests_and_production(): void
    {
        config(['cms.auth_enabled' => false]);
        $this->app['env'] = 'local';
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])->get('/admin')->assertRedirect('/admin/login');
        $this->app['env'] = 'production';
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])->get('/admin')->assertRedirect('/admin/login');
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['cms.auth_enabled' => true]);
    }

    public function test_cms_section_changes_are_visible_on_public_page_and_escaped(): void
    {
        $page = Page::create(['slug' => 'about', 'html_file' => 'about.html', 'title' => 'About']);
        $section = PageSection::create(['page_id' => $page->id, 'section_key' => 'section-2', 'heading' => 'A fresh travel story']);
        $this->get('/about')->assertOk()->assertSee('A fresh travel story');
        $section->update(['heading' => '<script>alert(1)</script>']);
        $this->get('/about')->assertOk()->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_media_upload_stores_safe_image_and_rejects_executable_content(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $image = UploadedFile::fake()->createWithContent('destination.png', base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aRZkAAAAASUVORK5CYII='));
        $this->actingAs($admin)->post('/admin/media', ['media_type' => 'image', 'upload' => $image])->assertSessionHasNoErrors();
        $asset = MediaAsset::firstOrFail();
        $this->assertStringStartsWith('storage/cms-media/', $asset->file_path);
        Storage::disk('public')->assertExists(substr($asset->file_path, 8));
        $this->post('/admin/media', ['media_type' => 'image', 'upload' => UploadedFile::fake()->create('shell.php', 1, 'application/x-httpd-php')])->assertSessionHasErrors('upload');
    }

    public function test_guests_and_regular_users_cannot_access_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
    }

    public function test_regular_credentials_cannot_login_as_admin(): void
    {
        User::factory()->create(['email' => 'member@example.com']);
        $this->post('/admin/login', ['email' => 'member@example.com', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_can_manage_destinations_and_unknown_modules_are_rejected(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Overview');
        $this->post('/admin/destinations', ['slug' => 'bali', 'name' => 'Bali', 'region' => 'Indonesia'])->assertSessionHasNoErrors()->assertRedirect('/admin/destinations');
        $this->assertDatabaseHas('destinations', ['slug' => 'bali']);
        $this->get('/admin/users')->assertNotFound();
        $this->post('/admin/destinations', ['slug' => 'bali', 'name' => 'Duplicate', 'region' => 'Indonesia'])->assertSessionHasErrors('slug');
    }

    public function test_media_rejects_traversal_and_executable_paths(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->post('/admin/media', ['file_path' => '../.env', 'media_type' => 'image'])->assertSessionHasErrors('file_path');
        $this->post('/admin/media', ['file_path' => 'assets/shell.php', 'media_type' => 'image'])->assertSessionHasErrors('file_path');
    }

    public function test_all_modules_render_and_pricing_plan_matches_database_column(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        foreach (app(AdminResourceRegistry::class)->all() as $slug => $definition) {
            $this->get('/admin/'.$slug)->assertOk();
            if ($definition['mode'] === 'crud') {
                $this->get('/admin/'.$slug.'/create')->assertOk();
            }
        }
        $this->post('/admin/pricing-plans', ['name' => 'Premium', 'price_per_person' => 12000000, 'currency' => 'IDR'])->assertSessionHasNoErrors()->assertRedirect('/admin/pricing-plans');
        $this->assertDatabaseHas('pricing_plans', ['name' => 'Premium', 'price_per_person' => 12000000]);
    }

    public function test_page_media_edit_preserves_existing_placement_and_settings_refresh_cache(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        $page = Page::create(['slug' => 'home', 'html_file' => 'index.html', 'title' => 'Home']);
        $media = MediaAsset::create(['file_path' => 'assets/hero.jpg', 'media_type' => 'image']);
        $page->media()->attach($media->id, ['placement' => 'hero', 'sort_order' => 3]);
        $this->put('/admin/pages/'.$page->id, ['slug' => 'home', 'html_file' => 'index.html', 'title' => 'Updated', 'media_ids' => [$media->id]])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('page_media', ['page_id' => $page->id, 'media_id' => $media->id, 'placement' => 'hero', 'sort_order' => 3]);
        SiteSetting::create(['setting_key' => 'brand', 'setting_value' => 'Old']);
        $this->assertSame('Old', SiteSetting::allKeyValues()['brand']);
        $this->put('/admin/settings/brand', ['setting_key' => 'brand', 'setting_value' => 'New'])->assertSessionHasNoErrors();
        $this->assertSame('New', SiteSetting::allKeyValues()['brand']);
    }

    public function test_contact_status_only_updates_allowed_fields_and_chat_is_readonly(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        $contact = ContactMessage::create(['full_name' => 'Customer', 'email' => 'customer@example.com', 'topic' => 'Trip', 'message' => 'Hello', 'reply_channel' => 'Email']);
        $this->put('/admin/contacts/'.$contact->id, ['status' => 'resolved', 'email' => 'attacker@example.com'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('contact_messages', ['id' => $contact->id, 'status' => 'resolved', 'email' => 'customer@example.com']);
        $this->get('/admin/contacts?status=new')->assertOk()->assertDontSee('customer@example.com');
        $this->get('/admin/contacts?status=resolved')->assertOk()->assertSee('customer@example.com');
        $this->get('/admin/contacts?status=invalid')->assertSessionHasErrors('status');
        $this->post('/admin/contacts', ['status' => 'new'])->assertForbidden();
        $chat = ChatSession::create(['public_token' => str_repeat('a', 64), 'model' => 'test', 'expires_at' => now()->addDay()]);
        $this->put('/admin/chats/'.$chat->id, [])->assertForbidden();
        $this->delete('/admin/chats/'.$chat->id)->assertForbidden();
    }

    public function test_package_crud_rejects_invalid_duration_and_protects_referenced_destination(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        $destination = Destination::create(['slug' => 'bali', 'name' => 'Bali', 'region' => 'Indonesia']);
        $data = ['title' => 'Bali Escape', 'slug' => 'bali-escape', 'destination_id' => $destination->id, 'description' => 'Island adventure', 'category' => 'beach', 'days' => 4, 'nights' => 3, 'price_per_person' => 5000000, 'currency' => 'IDR', 'review_count' => 0, 'is_luxury' => 0, 'is_active' => 1];
        $this->post('/admin/packages', $data)->assertSessionHasNoErrors()->assertRedirect('/admin/packages');
        $package = Package::where('slug', 'bali-escape')->firstOrFail();
        $this->get('/admin/packages/'.$package->id.'/edit')->assertOk()->assertSee('Bali Escape');
        $this->put('/admin/packages/'.$package->id, array_replace($data, ['nights' => 4]))->assertSessionHasErrors('nights');
        $this->delete('/admin/destinations/'.$destination->id)->assertSessionHas('error');
        $this->assertDatabaseHas('destinations', ['id' => $destination->id]);
        $this->put('/admin/packages/'.$package->id, array_replace($data, ['is_active' => 0]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('packages', ['id' => $package->id, 'is_active' => 0]);
        $this->delete('/admin/packages/'.$package->id)->assertSessionHas('success');
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_booking_status_changes_preserve_snapshot_and_records_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        $destination = Destination::create(['slug' => 'trip', 'name' => 'Trip', 'region' => 'Asia']);
        $package = Package::create(['slug' => 'trip', 'title' => 'Trip', 'destination_id' => $destination->id, 'description' => 'Trip', 'category' => 'beach', 'days' => 2, 'nights' => 1, 'price_per_person' => 5000000]);
        $booking = Booking::create(['reference_code' => 'TRV-TEST', 'package_id' => $package->id, 'departure_date' => '2027-01-01', 'departure_city' => 'Jakarta', 'travelers' => 2, 'lead_name' => 'Customer', 'lead_email' => 'customer@example.com', 'unit_price_snapshot' => 5000000, 'estimated_total' => 10000000, 'currency' => 'IDR']);
        $this->put('/admin/bookings/'.$booking->id, ['preview_status' => 'reviewed', 'estimated_total' => 1])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'preview_status' => 'reviewed', 'estimated_total' => 10000000]);
        $this->assertNotNull($booking->fresh()->reviewed_at);
        $this->put('/admin/bookings/'.$booking->id, ['preview_status' => 'paid'])->assertSessionHasErrors('preview_status');
        $this->delete('/admin/bookings/'.$booking->id)->assertForbidden();
        $this->delete('/admin/packages/'.$package->id)->assertSessionHas('error');
    }

    public function test_admin_command_creates_privileged_account_with_hashed_password(): void
    {
        $this->artisan('admin:create', ['email' => 'admin@example.com', '--name' => 'Admin'])
            ->expectsQuestion('Password (minimal 12 karakter, huruf besar/kecil, angka dan simbol)', 'Secure!Pass2026')
            ->expectsQuestion('Ulangi password', 'Secure!Pass2026')->expectsOutput('Akun admin berhasil dibuat.')->assertSuccessful();
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $this->assertTrue($admin->is_admin);
        $this->assertTrue(Hash::check('Secure!Pass2026', $admin->password));
    }

    public function test_search_pagination_and_primary_keys_cannot_be_changed(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);
        for ($index = 0; $index < 13; $index++) {
            Destination::create(['slug' => 'destination-'.$index, 'name' => 'Destination '.$index, 'region' => 'Asia']);
        }
        $this->get('/admin/destinations?q=Destination+12')->assertOk()->assertSee('Destination 12')->assertDontSee('Destination 11');
        $this->get('/admin/destinations?page=2')->assertOk()->assertSee('Destination 0');
        SiteSetting::create(['setting_key' => 'brand', 'setting_value' => 'Travel']);
        $this->put('/admin/settings/brand', ['setting_key' => 'other', 'setting_value' => 'Changed'])->assertSessionHasErrors('setting_key');
    }

    public function test_admin_login_logout_throttling_and_rendered_content_escaping(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $admin->forceFill(['is_admin' => true])->save();
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($admin);
        Destination::create(['slug' => 'safe', 'name' => '<script>alert(1)</script>', 'region' => 'Test']);
        $this->get('/admin/destinations')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);
        $this->post('/admin/logout')->assertRedirect('/admin/login');
        $this->assertGuest();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/admin/login', ['email' => 'other@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
        }
        $this->post('/admin/login', ['email' => 'other@example.com', 'password' => 'wrong'])->assertStatus(429);
    }
}
