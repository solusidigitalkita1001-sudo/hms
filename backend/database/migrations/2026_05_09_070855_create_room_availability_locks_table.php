<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_availability_locks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('locked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('lock_source')->default('manual');
            $table->timestamp('expires_at');
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'expires_at'], 'room_availability_locks_room_expiry_idx');
            $table->index(['reservation_id', 'released_at'], 'room_availability_locks_reservation_release_idx');
            $table->index(['locked_by_user_id', 'released_at'], 'room_availability_locks_user_release_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_availability_locks');
    }
};
