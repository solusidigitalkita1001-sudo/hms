<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->timestamp('expiry_at')->nullable()->after('booked_at');
            $table->timestamp('expired_at')->nullable()->after('cancelled_at');
            $table->timestamp('no_show_at')->nullable()->after('expired_at');
            $table->text('status_reason')->nullable()->after('internal_notes');

            $table->index(['reservation_status', 'expiry_at'], 'reservations_status_expiry_idx');
            $table->index(
                ['assigned_room_id', 'reservation_status', 'check_in_date'],
                'reservations_room_status_checkin_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex('reservations_status_expiry_idx');
            $table->dropIndex('reservations_room_status_checkin_idx');
            $table->dropColumn(['expiry_at', 'expired_at', 'no_show_at', 'status_reason']);
        });
    }
};
