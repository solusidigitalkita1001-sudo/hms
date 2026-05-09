<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->string('serviceability_status')->default('normal')->after('housekeeping_status');

            $table->index(
                ['current_status', 'housekeeping_status', 'serviceability_status', 'is_active'],
                'rooms_availability_state_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropIndex('rooms_availability_state_idx');
            $table->dropColumn('serviceability_status');
        });
    }
};
