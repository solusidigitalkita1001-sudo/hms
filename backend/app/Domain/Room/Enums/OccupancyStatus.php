<?php

namespace App\Domain\Room\Enums;

/**
 * Occupancy status — tracks whether a room is free, reserved, or occupied.
 */
enum OccupancyStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
}
