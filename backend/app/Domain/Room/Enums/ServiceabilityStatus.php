<?php

namespace App\Domain\Room\Enums;

/**
 * Serviceability status — tracks whether a room is fit for use or under maintenance.
 */
enum ServiceabilityStatus: string
{
    case Normal = 'normal';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';
}
