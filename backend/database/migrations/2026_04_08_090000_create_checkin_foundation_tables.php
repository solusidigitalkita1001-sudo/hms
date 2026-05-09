<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('full_name');
            $table->string('full_name_on_id')->nullable();
            $table->string('id_type', 40)->nullable();
            $table->string('id_number', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('nationality', 12)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_stay_at')->nullable();
            $table->unsignedInteger('total_stays')->default(0);
            $table->boolean('identity_verified')->default(false);
            $table->timestamp('identity_verified_at')->nullable();
            $table->foreignId('identity_verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('identity_verification_status')->default('not_started');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 40)->nullable();
            $table->boolean('is_blacklisted')->default(false);
            $table->text('blacklist_reason')->nullable();
            $table->timestamps();

            $table->index(['property_id', 'full_name']);
            $table->index('id_number');
            $table->index('phone');
            $table->index('identity_verification_status');
        });

        Schema::create('reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->foreignId('room_type_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('booking_code')->unique();
            $table->string('source')->default('direct');
            $table->string('reservation_status')->default('reserved');
            $table->unsignedInteger('adult_count')->default(1);
            $table->unsignedInteger('child_count')->default(0);
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->string('rate_plan_code', 60)->nullable();
            $table->string('payment_status')->default('pending');
            $table->string('guarantee_status')->default('pending');
            $table->decimal('deposit_amount', 15, 2)->default(0);
            $table->text('special_requests')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamp('arrived_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['property_id', 'reservation_status', 'check_in_date']);
            $table->index(['assigned_room_id', 'check_in_date']);
            $table->index('source');
        });

        Schema::create('reservation_guests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->string('full_name');
            $table->string('guest_role', 40)->default('companion');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_registered')->default(false);
            $table->string('id_type', 40)->nullable();
            $table->string('id_number', 120)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'is_primary']);
            $table->index('guest_role');
        });

        Schema::create('stay_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('primary_guest_id')->nullable()->constrained('guests')->nullOnDelete();
            $table->string('stay_status')->default('in_house');
            $table->timestamp('actual_check_in_at')->nullable();
            $table->timestamp('actual_check_out_at')->nullable();
            $table->timestamp('expected_check_out_at')->nullable();
            $table->foreignId('checked_in_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('primary_guest_name_snapshot');
            $table->boolean('registration_signed')->default(false);
            $table->timestamp('registration_signed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['room_id', 'stay_status']);
            $table->index(['reservation_id', 'stay_status']);
        });

        Schema::create('reservation_checkin_sessions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('arrival_status')->default('arrival_due');
            $table->string('current_step')->default('reservation');
            $table->string('id_verification_status')->default('not_started');
            $table->string('registration_status')->default('pending');
            $table->string('signature_status')->default('not_requested');
            $table->string('deposit_status')->default('pending');
            $table->text('override_reason')->nullable();
            $table->unsignedBigInteger('override_approved_by_user_id')->nullable();
            $table->unsignedBigInteger('started_by_user_id')->nullable();
            $table->unsignedBigInteger('completed_by_user_id')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('override_approved_by_user_id', 'rchk_sessions_override_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('started_by_user_id', 'rchk_sessions_started_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('completed_by_user_id', 'rchk_sessions_completed_user_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->index(['reservation_id', 'current_step'], 'rchk_sessions_reservation_step_idx');
            $table->index(['arrival_status', 'id_verification_status'], 'rchk_sessions_arrival_idv_idx');
        });

        Schema::create('front_desk_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('stay_record_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action_type', 80);
            $table->string('action_label');
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('payload_json')->nullable();
            $table->timestamp('happened_at');
            $table->timestamps();

            $table->index(['reservation_id', 'happened_at']);
            $table->index(['stay_record_id', 'happened_at']);
            $table->index('action_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('front_desk_audit_logs');
        Schema::dropIfExists('reservation_checkin_sessions');
        Schema::dropIfExists('stay_records');
        Schema::dropIfExists('reservation_guests');
        Schema::dropIfExists('reservations');
        Schema::dropIfExists('guests');
    }
};
