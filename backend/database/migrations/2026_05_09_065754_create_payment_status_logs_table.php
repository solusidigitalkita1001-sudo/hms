<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['payment_id', 'changed_at'], 'payment_status_logs_payment_changed_idx');
            $table->index(['reference_type', 'reference_id'], 'payment_status_logs_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_status_logs');
    }
};
