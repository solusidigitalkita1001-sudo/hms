<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stay_records', function (Blueprint $table): void {
            $table->date('check_in_business_date')->nullable()->after('stay_status');
            $table->date('check_out_business_date')->nullable()->after('actual_check_in_at');

            $table->index(['check_in_business_date', 'check_out_business_date'], 'stay_records_business_dates_idx');
        });
    }

    public function down(): void
    {
        Schema::table('stay_records', function (Blueprint $table): void {
            $table->dropIndex('stay_records_business_dates_idx');
            $table->dropColumn(['check_in_business_date', 'check_out_business_date']);
        });
    }
};
