<?php

namespace App\Domain\Room\Enums;

/**
 * Housekeeping status — tracks the cleanliness / inspection state of a room.
 */
enum HousekeepingStatus: string
{
    case Clean = 'clean';
    case Dirty = 'dirty';
    case Inspected = 'inspected';
}
