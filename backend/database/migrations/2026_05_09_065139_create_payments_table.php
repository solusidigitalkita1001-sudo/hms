<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('payment_code')->unique();
            $table->string('payment_type')->default('settlement');
            $table->string('payment_status')->default('pending');
            $table->string('payment_method_code', 60);
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('payment_reference')->nullable();
            $table->date('business_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('received_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['payment_status', 'paid_at'], 'payments_status_paid_at_idx');
            $table->index(['business_date', 'payment_method_code'], 'payments_business_method_idx');
            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
