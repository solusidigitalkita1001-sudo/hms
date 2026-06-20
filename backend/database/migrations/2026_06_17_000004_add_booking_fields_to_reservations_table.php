<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->string('booker_name')->nullable()->after('created_by_user_id');
            $table->string('booker_phone')->nullable()->after('booker_name');
            $table->boolean('is_booking_for_other')->default(false)->after('booker_phone');
            $table->string('guest_name_on_booking')->nullable()->after('is_booking_for_other');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn(['booker_name', 'booker_phone', 'is_booking_for_other', 'guest_name_on_booking']);
        });
    }
};
