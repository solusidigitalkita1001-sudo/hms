<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservation_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('full_name');
            $table->string('phone', 40);
            $table->string('email')->nullable();
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->unsignedTinyInteger('guest_count');
            $table->text('notes')->nullable();
            $table->string('source')->default('portal');
            $table->string('status')->default('new');
            $table->timestamps();

            $table->index(['property_id', 'status']);
            $table->index(['check_in_date', 'check_out_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservation_inquiries');
    }
};
