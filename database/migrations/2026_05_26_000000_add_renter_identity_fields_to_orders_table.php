<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('renter_name', 100)->nullable()->after('customer_name');
            $table->string('renter_phone', 20)->nullable()->after('renter_name');
            $table->string('event_purpose', 100)->nullable()->after('renter_phone');
            $table->text('notes')->nullable()->after('event_purpose');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'renter_name',
                'renter_phone',
                'event_purpose',
                'notes',
            ]);
        });
    }
};
