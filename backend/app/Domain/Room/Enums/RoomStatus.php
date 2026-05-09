<?php

namespace App\Domain\Room\Enums;

enum RoomStatus: string
{
    case Available = 'available';
    case Reserved = 'reserved';
    case Occupied = 'occupied';
    case Dirty = 'dirty';
    case Maintenance = 'maintenance';
    case OutOfService = 'out_of_service';
}
