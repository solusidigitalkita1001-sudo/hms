<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('night_audits', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->date('business_date');
            $table->date('next_business_date');
            $table->string('status')->default('completed');
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('summary_json')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'business_date'], 'night_audits_property_business_date_unique');
            $table->index(['property_id', 'status'], 'night_audits_property_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('night_audits');
    }
};
