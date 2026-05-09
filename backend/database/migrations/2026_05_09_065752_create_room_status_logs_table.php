<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('status_domain')->default('occupancy');
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('changed_at');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'status_domain', 'changed_at'], 'room_status_logs_room_domain_changed_idx');
            $table->index(['reference_type', 'reference_id'], 'room_status_logs_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_status_logs');
    }
};
