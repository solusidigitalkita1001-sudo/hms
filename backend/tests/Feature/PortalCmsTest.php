<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_cms_can_be_loaded_and_updated_with_auth_token(): void
    {
        $this->seed();

        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'identifier' => 'admin',
            'password' => 'password',
        ]);

        $token = $loginResponse->json('data.access_token');

        $payload = [
            'property_code' => 'MAIN',
            'announcement_badge' => 'Promo akhir pekan',
            'announcement_text' => 'Dapatkan rate fleksibel dan sarapan untuk dua tamu.',
            'announcement_link_label' => 'Lihat promo',
            'announcement_link_url' => '#explore',
            'hero_title' => 'Jakarta: stay yang lebih praktis',
            'hero_subtitle' => 'Portal yang dirancang untuk membantu tamu memilih kamar dan memahami properti dengan cepat.',
            'hero_image_url' => '',
            'hero_search_destination_label' => 'Destinasi Anda',
            'hero_search_destination_value' => 'Jakarta Pusat',
            'hero_search_date_label' => 'Tanggal stay',
            'hero_search_date_value' => '12 Mei → 14 Mei',
            'hero_search_room_label' => 'Kamar & tamu',
            'hero_search_room_value' => '1 kamar · 2 tamu',
            'hero_search_button_label' => 'Cari stay',
            'destinations_title' => 'Pilihan area unggulan',
            'explore_title' => 'Jelajahi highlight stay',
            'cta_title' => 'Butuh bantuan?',
            'cta_description' => 'Hubungi tim kami untuk penawaran corporate dan family stay.',
            'cta_primary_label' => 'Hubungi sekarang',
            'nav_items' => [
                ['label' => 'Destinasi', 'url' => '#destinations'],
                ['label' => 'Penawaran', 'url' => '#explore'],
            ],
            'destinations' => [
                ['title' => 'Sudirman', 'subtitle' => 'Dekat pusat bisnis.', 'image_url' => ''],
            ],
            'explore_filters' => [
                ['title' => 'Merek', 'items' => ['Business trip', 'Family stay']],
            ],
            'featured_hotels' => [
                [
                    'brand' => 'SUP',
                    'name' => 'Superior Room',
                    'description' => 'Pilihan efisien dengan workspace yang rapi.',
                    'image_url' => '',
                    'rating' => '4.7/5',
                    'location' => 'Jakarta',
                ],
            ],
        ];

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/settings/portal-cms', $payload)
            ->assertOk()
            ->assertJsonPath('data.hero_title', 'Jakarta: stay yang lebih praktis')
            ->assertJsonPath('data.destinations.0.title', 'Sudirman');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/settings/portal-cms?property_code=MAIN')
            ->assertOk()
            ->assertJsonPath('data.announcement_badge', 'Promo akhir pekan')
            ->assertJsonPath('data.featured_hotels.0.name', 'Superior Room');
    }
}
