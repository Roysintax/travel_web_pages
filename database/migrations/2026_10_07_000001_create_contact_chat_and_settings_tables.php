<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates contact_messages, chat_sessions, chat_messages, site_settings,
 * page_sections, and page_media tables if they do not exist.
 * Guarded with hasTable() for seamless MySQL existing database & SQLite in-memory test suite compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            Schema::create('site_settings', function (Blueprint $table) {
                $table->string('setting_key', 100)->primary();
                $table->text('setting_value');
                $table->string('description', 255)->nullable();
            });
        }

        if (! Schema::hasTable('page_sections')) {
            Schema::create('page_sections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->string('section_key', 100);
                $table->string('heading', 500)->nullable();
                $table->unique(['page_id', 'section_key']);
            });
        }

        if (! Schema::hasTable('page_media')) {
            Schema::create('page_media', function (Blueprint $table) {
                $table->id();
                $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
                $table->foreignId('media_id')->constrained('media_assets')->cascadeOnDelete();
                $table->string('placement', 40)->default('content');
                $table->unsignedInteger('sort_order')->default(0);
            });
        }

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->id();
                $table->string('full_name', 100);
                $table->string('email', 254);
                $table->string('phone', 40)->nullable();
                $table->string('topic', 100);
                $table->enum('reply_channel', ['Email', 'WhatsApp', 'Phone call'])->default('Email');
                $table->string('message', 600);
                $table->enum('status', ['new', 'in_progress', 'resolved'])->default('new');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['status', 'created_at']);
            });
        }

        if (! Schema::hasTable('chat_sessions')) {
            Schema::create('chat_sessions', function (Blueprint $table) {
                $table->id();
                $table->char('public_token', 64)->unique();
                $table->string('model', 150)->default('openai/gpt-oss-120b');
                $table->timestamp('created_at')->useCurrent();
                $table->dateTime('expires_at');
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('chat_sessions')->cascadeOnDelete();
                $table->enum('role', ['user', 'assistant']);
                $table->text('content');
                $table->timestamp('created_at')->useCurrent();

                $table->index(['session_id', 'id']);
            });
        }
    }

    public function down(): void
    {
        // Guarded: avoid dropping persistent tables accidentally
    }
};
