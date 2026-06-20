<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_condition_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reporter_type')->default('staff'); // staff or guest
            $table->string('guest_name')->nullable(); // guest name (when reporter_type=guest)
            $table->timestamp('report_time');
            $table->timestamp('window_expired_at')->nullable(); // optional 30-min window for guest reports
            $table->json('items'); // [{photo_url, category, description}]
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('acknowledged_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['reservation_id', 'room_id']);
            $table->index('acknowledged_at', 'rcr_acknowledged_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_condition_reports');
    }
};
