<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('bookings') && ! Schema::hasColumn('bookings', 'payment_status')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->string('payment_status', 30)->default('unpaid')->after('preview_status');
                $table->index('payment_status');
            });
        }

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('booking_id')
                    ->constrained('bookings')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
                $table->string('provider', 50)->default('midtrans');
                $table->string('provider_order_id', 100);
                $table->string('snap_token', 255)->nullable();
                $table->string('transaction_id', 100)->nullable();
                $table->string('payment_type', 50)->nullable();
                $table->string('transaction_status', 50)->default('pending');
                $table->string('fraud_status', 50)->nullable();
                $table->decimal('gross_amount', 15, 2);
                $table->char('currency', 3)->default('IDR');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('expired_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'provider_order_id']);
                $table->unique('transaction_id');
                $table->index(['booking_id', 'transaction_status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'payment_status')) {
            Schema::table('bookings', function (Blueprint $table): void {
                $table->dropIndex(['payment_status']);
                $table->dropColumn('payment_status');
            });
        }
    }
};
