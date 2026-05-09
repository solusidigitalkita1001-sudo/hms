<?php

namespace App\Application\Portal\DataTransferObjects;

class GuestPortalData
{
    public function __construct(
        public readonly array $property,
        public readonly array $branding,
        public readonly array $cms,
        public readonly array $facilities,
        public readonly array $availableRoomTypes,
        public readonly array $recommendations,
        public readonly array $summary,
    ) {}

    public function toArray(): array
    {
        return [
            'property' => $this->property,
            'branding' => $this->branding,
            'cms' => $this->cms,
            'facilities' => $this->facilities,
            'available_room_types' => $this->availableRoomTypes,
            'recommendations' => $this->recommendations,
            'summary' => $this->summary,
        ];
    }
}
