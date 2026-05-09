<?php

namespace App\Application\Dashboard\Services;

use App\Application\Dashboard\DataTransferObjects\DashboardSummaryData;
use App\Domain\Billing\Models\Invoice;
use App\Domain\Housekeeping\Models\HousekeepingTask;
use App\Domain\Inventory\Models\InventoryItem;
use App\Domain\Room\Enums\RoomStatus;
use App\Domain\Room\Models\Room;
use Carbon\CarbonImmutable;

class DashboardSummaryService
{
    public function handle(): DashboardSummaryData
    {
        $today = CarbonImmutable::today();

        return new DashboardSummaryData(
            availableRooms: Room::query()->where('current_status', RoomStatus::Available->value)->count(),
            occupiedRooms: Room::query()->where('current_status', RoomStatus::Occupied->value)->count(),
            dirtyRooms: Room::query()->where('current_status', RoomStatus::Dirty->value)->count(),
            maintenanceRooms: Room::query()->where('current_status', RoomStatus::Maintenance->value)->count(),
            pendingHousekeepingTasks: HousekeepingTask::query()->whereIn('task_status', ['pending', 'assigned', 'in_progress'])->count(),
            lowStockItems: InventoryItem::query()->whereColumn('current_stock', '<=', 'minimum_stock')->count(),
            todayRevenue: (float) Invoice::query()
                ->whereDate('issued_at', $today)
                ->sum('paid_amount'),
        );
    }
}
