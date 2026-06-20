<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->decimal('size_sqm', 8, 2)->nullable()->after('description');
            $table->string('bed_type')->nullable()->after('size_sqm');
            $table->boolean('smoking_allowed')->default(false)->after('bed_type');
            $table->text('amenities')->nullable()->after('smoking_allowed');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropColumn(['amenities', 'smoking_allowed', 'bed_type', 'size_sqm']);
        });
    }
};
