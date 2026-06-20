<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_loans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('loanable_assets')->cascadeOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('loaned_at');
            $table->timestamp('returned_at')->nullable();
            $table->string('return_condition')->nullable(); // good / damaged / lost
            $table->unsignedBigInteger('charge_amount')->nullable(); // in cents/smallest currency unit
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'asset_id']);
            $table->index('returned_at', 'asset_loans_returned_at_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_loans');
    }
};
