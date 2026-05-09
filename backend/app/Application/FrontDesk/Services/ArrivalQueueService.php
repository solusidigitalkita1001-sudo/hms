<?php

namespace App\Application\FrontDesk\Services;

use App\Domain\Reservation\Models\Reservation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ArrivalQueueService
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $sortBy = $filters['sort_by'] ?? 'check_in_date';
        $sortDirection = $filters['sort_direction'] ?? 'asc';
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 5), 100);
        $search = trim((string) ($filters['search'] ?? ''));
        $status = $filters['status'] ?? null;
        $propertyId = $filters['property_id'] ?? null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $source = $filters['source'] ?? null;

        $allowedSorts = [
            'booking_code',
            'check_in_date',
            'check_out_date',
            'reservation_status',
            'source',
            'created_at',
        ];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'check_in_date';
        }

        $query = Reservation::query()
            ->with([
                'property:id,code,name',
                'primaryGuest:id,full_name,phone,email,identity_verified,identity_verification_status',
                'roomType:id,code,name',
                'assignedRoom:id,room_number,current_status',
            ]);

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('primaryGuest', function ($guestQuery) use ($search): void {
                        $guestQuery
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($status) {
            $query->where('reservation_status', $status);
        }

        if ($propertyId) {
            $query->where('property_id', $propertyId);
        }

        if ($dateFrom) {
            $query->whereDate('check_in_date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('check_in_date', '<=', $dateTo);
        }

        if ($source) {
            $query->where('source', $source);
        }

        return $query
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage)
            ->withQueryString();
    }
}
