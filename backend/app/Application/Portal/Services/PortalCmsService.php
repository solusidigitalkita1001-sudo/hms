<?php

namespace App\Application\Portal\Services;

use App\Domain\Property\Models\Property;
use App\Domain\Room\Models\RoomType;
use App\Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\DB;

class PortalCmsService
{
    public function getContent(string $propertyCode = 'MAIN'): array
    {
        $property = $this->resolveProperty($propertyCode);
        $settings = $this->portalSettings($property->id);

        return [
            'property_code' => $property->code,
            'announcement_badge' => $settings['announcement_badge'] ?? 'Update perjalanan',
            'announcement_text' => $settings['announcement_text'] ?? 'Reservasi fleksibel, check-in rapi, dan pengalaman tamu dibuat lebih nyaman.',
            'announcement_link_label' => $settings['announcement_link_label'] ?? 'Lihat ketentuan',
            'announcement_link_url' => $settings['announcement_link_url'] ?? '#explore',
            'nav_items' => $this->decodeArraySetting(
                $settings['nav_items'] ?? null,
                [
                    ['label' => 'Destinasi', 'url' => '#destinations'],
                    ['label' => 'Penawaran', 'url' => '#explore'],
                    ['label' => 'Fasilitas', 'url' => '#facilities'],
                    ['label' => 'Kamar', 'url' => '#rooms'],
                    ['label' => 'Kontak', 'url' => '#contact'],
                ],
            ),
            'hero_title' => $settings['hero_title'] ?? sprintf('%s: pesan stay Anda dengan lebih nyaman', $property->name),
            'hero_subtitle' => $settings['hero_subtitle'] ?? 'Portal modern untuk bantu tamu memahami properti, penawaran kamar, dan highlight area sekitar dalam satu halaman yang enak dilihat.',
            'hero_image_url' => $settings['hero_image_url'] ?? '',
            'hero_search_destination_label' => $settings['hero_search_destination_label'] ?? 'Mau menginap di mana?',
            'hero_search_destination_value' => $settings['hero_search_destination_value'] ?? $property->address,
            'hero_search_date_label' => $settings['hero_search_date_label'] ?? 'Kapan Anda akan tiba?',
            'hero_search_date_value' => $settings['hero_search_date_value'] ?? '04 April → 05 April',
            'hero_search_room_label' => $settings['hero_search_room_label'] ?? 'Kamar & tamu',
            'hero_search_room_value' => $settings['hero_search_room_value'] ?? '1 kamar · 2 tamu',
            'hero_search_button_label' => $settings['hero_search_button_label'] ?? 'Lihat pilihan kamar',
            'destinations_title' => $settings['destinations_title'] ?? 'Destinasi dan vibe sekitar hotel',
            'destinations' => $this->decodeArraySetting(
                $settings['destinations'] ?? null,
                [
                    [
                        'title' => 'Business District',
                        'subtitle' => 'Area strategis dekat pusat meeting dan gedung perkantoran.',
                        'image_url' => '',
                    ],
                    [
                        'title' => 'Lifestyle Spots',
                        'subtitle' => 'Pilihan kuliner, coffee shop, dan tempat santai setelah meeting.',
                        'image_url' => '',
                    ],
                    [
                        'title' => 'City Weekend',
                        'subtitle' => 'Cocok untuk staycation singkat dengan akses transportasi yang mudah.',
                        'image_url' => '',
                    ],
                ],
            ),
            'explore_title' => $settings['explore_title'] ?? sprintf('%s: jelajahi pilihan stay', $property->name),
            'explore_filters' => $this->decodeArraySetting(
                $settings['explore_filters'] ?? null,
                [
                    [
                        'title' => 'Merek',
                        'items' => ['Signature stay', 'Business trip', 'Family stay'],
                    ],
                    [
                        'title' => 'Nilai pelanggan',
                        'items' => ['All', '4 dan +', 'Best seller'],
                    ],
                    [
                        'title' => 'Fasilitas',
                        'items' => ['Kolam renang', 'Sarapan', 'Airport transfer'],
                    ],
                ],
            ),
            'featured_hotels' => $this->decodeArraySetting(
                $settings['featured_hotels'] ?? null,
                $this->defaultFeaturedHotels($property->id),
            ),
            'cta_title' => $settings['cta_title'] ?? 'Butuh bantuan reservasi?',
            'cta_description' => $settings['cta_description'] ?? 'Gunakan portal ini sebagai landing page yang terasa premium saat tamu scan QR di lobby, buka link dari WhatsApp, atau lihat pre-arrival info.',
            'cta_primary_label' => $settings['cta_primary_label'] ?? 'Hubungi front desk',
        ];
    }

    public function update(array $payload): array
    {
        $property = $this->resolveProperty($payload['property_code'] ?? 'MAIN');

        return DB::transaction(function () use ($payload, $property): array {
            $settings = [
                'announcement_badge' => $payload['announcement_badge'],
                'announcement_text' => $payload['announcement_text'],
                'announcement_link_label' => $payload['announcement_link_label'],
                'announcement_link_url' => $payload['announcement_link_url'] ?? '',
                'hero_title' => $payload['hero_title'],
                'hero_subtitle' => $payload['hero_subtitle'],
                'hero_image_url' => $payload['hero_image_url'] ?? '',
                'hero_search_destination_label' => $payload['hero_search_destination_label'],
                'hero_search_destination_value' => $payload['hero_search_destination_value'],
                'hero_search_date_label' => $payload['hero_search_date_label'],
                'hero_search_date_value' => $payload['hero_search_date_value'],
                'hero_search_room_label' => $payload['hero_search_room_label'],
                'hero_search_room_value' => $payload['hero_search_room_value'],
                'hero_search_button_label' => $payload['hero_search_button_label'],
                'destinations_title' => $payload['destinations_title'],
                'explore_title' => $payload['explore_title'],
                'cta_title' => $payload['cta_title'],
                'cta_description' => $payload['cta_description'],
                'cta_primary_label' => $payload['cta_primary_label'],
                'nav_items' => $this->encodeArraySetting($payload['nav_items']),
                'destinations' => $this->encodeArraySetting($payload['destinations']),
                'explore_filters' => $this->encodeArraySetting($payload['explore_filters']),
                'featured_hotels' => $this->encodeArraySetting($payload['featured_hotels']),
            ];

            foreach ($settings as $key => $value) {
                Setting::query()->updateOrCreate(
                    [
                        'property_id' => $property->id,
                        'setting_group' => 'portal_cms',
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $value,
                    ],
                );
            }

            return $this->getContent($property->code);
        });
    }

    private function resolveProperty(string $propertyCode): Property
    {
        return Property::query()
            ->where('code', strtoupper($propertyCode))
            ->where('is_active', true)
            ->firstOrFail();
    }

    private function portalSettings(int $propertyId): array
    {
        return Setting::query()
            ->where('property_id', $propertyId)
            ->where('setting_group', 'portal_cms')
            ->orderBy('setting_key')
            ->get()
            ->mapWithKeys(fn (Setting $setting): array => [$setting->setting_key => $setting->setting_value])
            ->toArray();
    }

    private function decodeArraySetting(?string $value, array $default): array
    {
        if (! $value) {
            return $default;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : $default;
    }

    private function encodeArraySetting(array $value): string
    {
        return (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function defaultFeaturedHotels(int $propertyId): array
    {
        return RoomType::query()
            ->where('property_id', $propertyId)
            ->where('is_active', true)
            ->orderBy('base_price')
            ->get()
            ->map(fn (RoomType $roomType): array => [
                'brand' => $roomType->code,
                'name' => $roomType->name,
                'description' => $roomType->description ?? 'Pilihan stay yang dirancang untuk pengalaman tamu yang lebih nyaman.',
                'image_url' => '',
                'rating' => $roomType->capacity >= 4 ? '4.9/5' : '4.7/5',
                'location' => 'Jakarta',
            ])
            ->toArray();
    }
}
