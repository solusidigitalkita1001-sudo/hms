<?php

namespace App\Domain\Room\Enums;

/**
 * Legacy room status enum — kept for backward compatibility.
 *
 * The system now uses three separate dimensions:
 * - OccupancyStatus (available / reserved / occupied)
 * - HousekeepingStatus (clean / dirty / inspected)
 * - ServiceabilityStatus (normal / maintenance / out_of_service)
 *
 * @see OccupancyStatus
 * @see HousekeepingStatus
 * @see ServiceabilityStatus
 */
enum RoomStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Dirty = 'dirty';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';

    /**
     * Map to the OccupancyStatus equivalent (for values that are occupancy-related).
     */
    public function toOccupancy(): ?OccupancyStatus
    {
        return match ($this) {
            self::Available => OccupancyStatus::Available,
            self::Reserved => OccupancyStatus::Reserved,
            self::Occupied => OccupancyStatus::Occupied,
            default => null,
        };
    }

    /**
     * Map to the HousekeepingStatus equivalent (for values that are housekeeping-related).
     */
    public function toHousekeeping(): ?HousekeepingStatus
    {
        return match ($this) {
            self::Dirty => HousekeepingStatus::Dirty,
            default => null,
        };
    }

    /**
     * Map to the ServiceabilityStatus equivalent (for values that are serviceability-related).
     */
    public function toServiceability(): ?ServiceabilityStatus
    {
        return match ($this) {
            self::Maintenance => ServiceabilityStatus::Maintenance,
            self::OutOfService => ServiceabilityStatus::OutOfService,
            default => null,
        };
    }
}
