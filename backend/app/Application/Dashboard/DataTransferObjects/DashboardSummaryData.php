<?php

namespace App\Application\Dashboard\DataTransferObjects;

class DashboardSummaryData
{
    public function __construct(
        public readonly int $availableRooms,
        public readonly int $occupiedRooms,
        public readonly int $dirtyRooms,
        public readonly int $maintenanceRooms,
        public readonly int $pendingHousekeepingTasks,
        public readonly int $lowStockItems,
        public readonly float $todayRevenue,
    ) {}

    public function toArray(): array
    {
        return [
            'available_rooms' => $this->availableRooms,
            'occupied_rooms' => $this->occupiedRooms,
            'dirty_rooms' => $this->dirtyRooms,
            'maintenance_rooms' => $this->maintenanceRooms,
            'pending_housekeeping_tasks' => $this->pendingHousekeepingTasks,
            'low_stock_items' => $this->lowStockItems,
            'today_revenue' => $this->todayRevenue,
        ];
    }
}
