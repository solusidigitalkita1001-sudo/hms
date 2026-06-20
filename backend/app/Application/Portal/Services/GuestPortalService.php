<?php

namespace App\Application\Portal\Services;

use App\Application\Portal\DataTransferObjects\GuestPortalData;
use App\Domain\Property\Models\Property;
use App\Domain\Property\Models\PropertyFacility;
use App\Domain\Room\Enums\OccupancyStatus;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Domain\Setting\Models\Setting;

class GuestPortalService
{
    public function __construct(
        private readonly PortalCmsService $portalCmsService,
    ) {}

    public function handle(string $propertyCode): GuestPortalData
    {
        $property = Property::query()
            ->where('code', strtoupper($propertyCode))
            ->where('is_active', true)
            ->firstOrFail();

        $settings = Setting::query()
            ->where(function ($query) use ($property): void {
                $query
                    ->where('property_id', $property->id)
                    ->orWhereNull('property_id');
            })
            ->whereIn('setting_group', ['branding', 'business', 'portal', 'ui'])
            ->orderByRaw('property_id is null')
            ->get()
            ->groupBy('setting_group')
            ->map(fn ($items) => $items->mapWithKeys(
                fn (Setting $setting): array => [$setting->setting_key => $setting->setting_value]
            )->toArray())
            ->toArray();

        $facilities = PropertyFacility::query()
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get()
            ->map(fn (PropertyFacility $facility): array => [
                'name' => $facility->name,
                'icon' => $facility->icon,
                'description' => $facility->description,
                'is_featured' => $facility->is_featured,
            ])
            ->toArray();

        $availableRoomTypes = RoomType::query()
            ->where('property_id', $property->id)
            ->where('is_active', true)
            ->get()
            ->map(function (RoomType $roomType): array {
                $availableRooms = Room::query()
                    ->where('room_type_id', $roomType->id)
                    ->where('is_active', true)
                    ->where('current_status', OccupancyStatus::Available->value)
                    ->count();

                return [
                    'code' => $roomType->code,
                    'name' => $roomType->name,
                    'description' => $roomType->description,
                    'capacity' => $roomType->capacity,
                    'base_price' => (float) $roomType->base_price,
                    'weekend_price' => (float) $roomType->weekend_price,
                    'available_rooms' => $availableRooms,
                    'starting_from' => (float) $roomType->base_price,
                    'is_available' => $availableRooms > 0,
                ];
            })
            ->sortByDesc('available_rooms')
            ->values()
            ->toArray();

        $topRecommendation = collect($availableRoomTypes)
            ->first(fn (array $roomType): bool => $roomType['available_rooms'] > 0);

        $familyRecommendation = collect($availableRoomTypes)
            ->first(fn (array $roomType): bool => $roomType['capacity'] >= 3 && $roomType['available_rooms'] > 0);

        $valueRecommendation = collect($availableRoomTypes)
            ->filter(fn (array $roomType): bool => $roomType['available_rooms'] > 0)
            ->sortBy('starting_from')
            ->first();

        $recommendations = array_values(array_filter([
            $topRecommendation ? [
                'title' => sprintf('%s paling siap dipesan', $topRecommendation['name']),
                'description' => sprintf(
                    '%d kamar tersedia dengan harga mulai %s.',
                    $topRecommendation['available_rooms'],
                    $this->formatCurrency($topRecommendation['starting_from'], $property->currency),
                ),
                'tag' => 'Top Pick',
            ] : null,
            $valueRecommendation ? [
                'title' => sprintf('%s untuk best value', $valueRecommendation['name']),
                'description' => sprintf(
                    'Pilihan paling ekonomis untuk tamu yang ingin stay nyaman dengan budget efisien mulai %s.',
                    $this->formatCurrency($valueRecommendation['starting_from'], $property->currency),
                ),
                'tag' => 'Value',
            ] : null,
            $familyRecommendation ? [
                'title' => sprintf('%s cocok untuk grup kecil', $familyRecommendation['name']),
                'description' => sprintf(
                    'Kapasitas hingga %d tamu dan tetap tersedia untuk booking langsung.',
                    $familyRecommendation['capacity'],
                ),
                'tag' => 'Family',
            ] : null,
        ]));

        return new GuestPortalData(
            property: [
                'code' => $property->code,
                'name' => $property->name,
                'address' => $property->address,
                'phone' => $property->phone,
                'email' => $property->email,
                'timezone' => $property->timezone,
                'currency' => $property->currency,
            ],
            branding: [
                'app_name' => $settings['branding']['app_name'] ?? $property->name,
                'tagline' => $settings['portal']['tagline'] ?? 'Stay nyaman, operasional cepat, dan pengalaman tamu lebih rapi.',
                'hero_title' => $settings['portal']['hero_title'] ?? sprintf('Selamat datang di %s', $property->name),
                'hero_description' => $settings['portal']['hero_description'] ?? 'Temukan fasilitas unggulan, cek kamar yang masih tersedia, dan pilih stay yang paling cocok untuk kebutuhan Anda.',
                'check_in_time' => $settings['business']['check_in_time'] ?? '14:00',
                'check_out_time' => $settings['business']['check_out_time'] ?? '12:00',
                'primary_color' => $settings['ui']['primary_color'] ?? '#2563eb',
            ],
            cms: $this->portalCmsService->getContent($property->code),
            facilities: $facilities,
            availableRoomTypes: $availableRoomTypes,
            recommendations: $recommendations,
            summary: [
                'available_rooms' => Room::query()
                    ->where('property_id', $property->id)
                    ->where('current_status', OccupancyStatus::Available->value)
                    ->where('is_active', true)
                    ->count(),
                'occupied_rooms' => Room::query()
                    ->where('property_id', $property->id)
                    ->where('current_status', OccupancyStatus::Occupied->value)
                    ->where('is_active', true)
                    ->count(),
                'featured_facilities' => count(array_filter($facilities, fn (array $facility): bool => $facility['is_featured'])),
            ],
        );
    }

    private function formatCurrency(float $amount, string $currency): string
    {
        $currencyCode = strtoupper($currency);

        if ($currencyCode === 'IDR') {
            return 'Rp '.number_format($amount, 0, ',', '.');
        }

        return $currencyCode.' '.number_format($amount, 2, '.', ',');
    }
}
