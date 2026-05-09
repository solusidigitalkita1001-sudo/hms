<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stay_guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stay_record_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->string('occupancy_role', 40)->default('occupant');
            $table->timestamp('identity_verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['stay_record_id', 'is_primary'], 'stay_guests_stay_primary_idx');
            $table->index(['guest_id', 'occupancy_role'], 'stay_guests_guest_role_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_guests');
    }
};
