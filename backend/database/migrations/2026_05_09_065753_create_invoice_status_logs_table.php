<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_status_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['invoice_id', 'changed_at'], 'invoice_status_logs_invoice_changed_idx');
            $table->index(['reference_type', 'reference_id'], 'invoice_status_logs_reference_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_status_logs');
    }
};
