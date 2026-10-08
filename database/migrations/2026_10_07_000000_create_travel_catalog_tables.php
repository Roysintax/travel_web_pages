<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mirrors the catalog tables from convert/database/travel_hajj.sql.
 * Guarded with hasTable() so it never touches the already-imported MySQL database;
 * mainly used by the SQLite in-memory test suite.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('media_assets')) {
            Schema::create('media_assets', function (Blueprint $table) {
                $table->id();
                $table->string('file_path')->unique();
                $table->enum('media_type', ['image', 'video']);
                $table->string('alt_text', 500)->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (! Schema::hasTable('destinations')) {
            Schema::create('destinations', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 100)->unique();
                $table->string('name', 150);
                $table->string('region', 80);
                $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            });
        }

        if (! Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 100)->unique();
                $table->string('booking_key', 40)->nullable()->unique();
                $table->foreignId('destination_id')->constrained('destinations');
                $table->foreignId('image_id')->nullable()->constrained('media_assets')->nullOnDelete();
                $table->string('title', 180);
                $table->text('description');
                $table->string('category', 40);
                $table->string('badge', 80)->nullable();
                $table->unsignedSmallInteger('days');
                $table->unsignedSmallInteger('nights');
                $table->decimal('price_per_person', 15, 2);
                $table->decimal('original_price', 15, 2)->nullable();
                $table->char('currency', 3)->default('IDR');
                $table->decimal('rating', 2, 1)->nullable();
                $table->unsignedInteger('review_count')->default(0);
                $table->boolean('is_luxury')->default(false);
                $table->boolean('is_active')->default(true);
            });
        }

        if (! Schema::hasTable('package_items')) {
            Schema::create('package_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->enum('item_type', ['feature', 'included']);
                $table->text('description');
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('package_itineraries')) {
            Schema::create('package_itineraries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->unsignedSmallInteger('day_number');
                $table->string('title', 255);
                $table->text('description');
            });
        }

        if (! Schema::hasTable('package_accommodations')) {
            Schema::create('package_accommodations', function (Blueprint $table) {
                $table->foreignId('package_id')->primary()->constrained('packages')->cascadeOnDelete();
                $table->string('name', 255);
                $table->string('star_label', 80)->nullable();
                $table->text('perks')->nullable();
            });
        }

        if (! Schema::hasTable('pricing_plans')) {
            Schema::create('pricing_plans', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->decimal('price_per_person', 15, 2);
                $table->char('currency', 3)->default('IDR');
            });
        }

        if (! Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 80)->unique();
                $table->string('html_file', 100)->unique();
                $table->string('title', 255);
                $table->text('meta_description')->nullable();
            });
        }

        if (! Schema::hasTable('faqs')) {
            Schema::create('faqs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->string('question', 500);
                $table->text('answer');
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('bookings')) {
            Schema::create('bookings', function (Blueprint $table) {
                $table->id();
                $table->string('reference_code', 40)->unique();
                $table->foreignId('package_id')->constrained('packages');
                $table->date('departure_date');
                $table->string('departure_city', 100);
                $table->unsignedTinyInteger('travelers');
                $table->string('lead_name', 100);
                $table->string('lead_email', 254);
                $table->string('notes', 500)->nullable();
                $table->decimal('unit_price_snapshot', 15, 2);
                $table->char('currency', 3)->default('IDR');
                $table->decimal('estimated_total', 17, 2)->nullable();
                $table->enum('preview_status', ['draft', 'reviewed', 'ready'])->default('draft');
                $table->dateTime('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Intentionally empty: these tables hold existing catalog data and must not be dropped by rollback.
    }
};
