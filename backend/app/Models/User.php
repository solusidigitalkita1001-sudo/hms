<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Domain\Auth\Models\ApiToken;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Billing\Models\InvoiceStatusLog;
use App\Domain\Billing\Models\Payment;
use App\Domain\Billing\Models\PaymentStatusLog;
use App\Domain\FrontDesk\Models\FrontDeskAuditLog;
use App\Domain\Reservation\Models\Reservation;
use App\Domain\Reservation\Models\ReservationStatusLog;
use App\Domain\Room\Models\RoomAvailabilityLock;
use App\Domain\Room\Models\RoomStatusLog;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'employee_id',
        'role_id',
        'name',
        'username',
        'email',
        'google_id',
        'avatar_url',
        'password',
        'is_active',
        'email_verified_at',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function createdReservations(): HasMany
    {
        return $this->hasMany(Reservation::class, 'created_by_user_id');
    }

    public function createdInvoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'created_by_user_id');
    }

    public function receivedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'received_by_user_id');
    }

    public function roomStatusLogs(): HasMany
    {
        return $this->hasMany(RoomStatusLog::class, 'changed_by_user_id');
    }

    public function reservationStatusLogs(): HasMany
    {
        return $this->hasMany(ReservationStatusLog::class, 'changed_by_user_id');
    }

    public function invoiceStatusLogs(): HasMany
    {
        return $this->hasMany(InvoiceStatusLog::class, 'changed_by_user_id');
    }

    public function paymentStatusLogs(): HasMany
    {
        return $this->hasMany(PaymentStatusLog::class, 'changed_by_user_id');
    }

    public function roomAvailabilityLocks(): HasMany
    {
        return $this->hasMany(RoomAvailabilityLock::class, 'locked_by_user_id');
    }

    public function frontDeskAuditLogs(): HasMany
    {
        return $this->hasMany(FrontDeskAuditLog::class, 'actor_user_id');
    }
}
