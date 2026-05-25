<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('order_status');
            $table->string('payment_status')->default('pending')->change();
            $table->string('payment_reference')->nullable()->after('payment_status');
            $table->string('payment_gateway')->nullable()->after('payment_reference');
            $table->json('payment_payload')->nullable()->after('payment_gateway');
            $table->string('validation_token')->nullable()->unique()->after('payment_payload');
            $table->timestamp('paid_at')->nullable()->after('validation_token');
        });

        DB::table('orders')
            ->where('payment_status', 'unpaid')
            ->update(['payment_status' => 'pending']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['validation_token']);
            $table->dropColumn([
                'payment_method',
                'payment_reference',
                'payment_gateway',
                'payment_payload',
                'validation_token',
                'paid_at',
            ]);
            $table->string('payment_status')->default('pending')->change();
        });
    }
};
