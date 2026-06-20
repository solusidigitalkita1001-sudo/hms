<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_availability_locks', function (Blueprint $table): void {
            $table->date('lock_start_date')->nullable()->after('locked_by_user_id');
            $table->date('lock_end_date')->nullable()->after('lock_start_date');
            $table->string('lock_type')->default('reservation')->after('lock_source');
            $table->text('notes')->nullable()->after('release_reason');
        });
    }

    public function down(): void
    {
        Schema::table('room_availability_locks', function (Blueprint $table): void {
            $table->dropColumn(['lock_start_date', 'lock_end_date', 'lock_type', 'notes']);
        });
    }
};
