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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->onDelete('set null');

            // Customer data merged from the add-column migration.
            $table->string('customer_name')->nullable();
            $table->string('identity_photo')->nullable();
            $table->string('bukti_pembayaran')->nullable();
            $table->string('source')->default('online'); // online or offline
            $table->text('address')->nullable();

            // Rental and pricing data.
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('rent_days');
            $table->decimal('price_per_day', 12, 2);
            $table->decimal('total_price', 12, 2);

            // Use strings to avoid data truncation errors.
            $table->string('order_status')->default('pending'); // pending, approved, rented, returned, cancelled
            $table->string('payment_status')->default('pending'); // pending, paid, failed, expired

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
