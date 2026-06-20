<?php

namespace App\Application\Settings\Actions;

use App\Application\Settings\Services\BusinessDateService;
use App\Domain\Property\Models\NightAudit;
use App\Domain\Property\Models\Property;
use App\Domain\Reservation\Models\StayRecord;
use App\Domain\Room\Models\RoomAvailabilityLock;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RunNightAuditAction
{
    public function __construct(
        private readonly BusinessDateService $businessDateService,
    ) {}

    public function handle(Property $property, int $actorUserId, ?string $notes = null): NightAudit
    {
        return DB::transaction(function () use ($property, $actorUserId, $notes): NightAudit {
            $property = Property::query()
                ->lockForUpdate()
                ->findOrFail($property->id);

            $currentBusinessDate = $this->businessDateService->currentBusinessDate($property);
            $nextBusinessDate = $currentBusinessDate->addDay();

            $existingAudit = NightAudit::query()
                ->where('property_id', $property->id)
                ->whereDate('business_date', $currentBusinessDate->toDateString())
                ->first();

            if ($existingAudit?->status === 'completed') {
                throw ValidationException::withMessages([
                    'business_date' => 'Night audit untuk business date ini sudah pernah dijalankan.',
                ]);
            }

            $startedAt = now($property->timezone);

            $audit = NightAudit::query()->updateOrCreate(
                [
                    'property_id' => $property->id,
                    'business_date' => $currentBusinessDate->toDateString(),
                ],
                [
                    'next_business_date' => $nextBusinessDate->toDateString(),
                    'status' => 'completed',
                    'closed_by_user_id' => $actorUserId,
                    'started_at' => $existingAudit?->started_at ?? $startedAt,
                    'completed_at' => $startedAt,
                    'notes' => $notes,
                    'summary_json' => [
                        'open_room_locks' => RoomAvailabilityLock::query()
                            ->where('property_id', $property->id)
                            ->whereNull('released_at')
                            ->count(),
                        'in_house_stays' => StayRecord::query()
                            ->where('property_id', $property->id)
                            ->where('stay_status', 'in_house')
                            ->count(),
                    ],
                ],
            );

            $this->businessDateService->updateCurrentBusinessDate($property, $nextBusinessDate);

            return $audit->fresh(['property', 'closedByUser']);
        });
    }
}
