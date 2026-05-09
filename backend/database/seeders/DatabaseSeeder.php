<?php

namespace Database\Seeders;

use App\Domain\Property\Models\PropertyFacility;
use App\Domain\Room\Models\Room;
use App\Domain\Room\Models\RoomType;
use App\Domain\Setting\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $propertyId = DB::table('properties')->insertGetId([
                'code' => 'MAIN',
                'name' => 'Booking WPA Hotel',
                'address' => 'Jl. Sudirman No. 88, Jakarta',
                'phone' => '+62 812-0000-7788',
                'email' => 'stay@bookingwpa.test',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $roleId = DB::table('roles')->insertGetId([
                'name' => 'Super Admin',
                'code' => 'super_admin',
                'description' => 'Full access to the application.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $employeeId = DB::table('employees')->insertGetId([
                'property_id' => $propertyId,
                'employee_code' => 'EMP-0001',
                'full_name' => 'System Administrator',
                'email' => 'admin@local.test',
                'job_title' => 'Owner',
                'department' => 'Management',
                'employment_status' => 'active',
                'base_salary' => 0,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            User::factory()->create([
                'employee_id' => $employeeId,
                'role_id' => $roleId,
                'name' => 'System Administrator',
                'username' => 'admin',
                'email' => 'admin@local.test',
            ]);

            Setting::query()->upsert([
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'branding',
                    'setting_key' => 'app_name',
                    'setting_value' => 'Booking WPA Hotel',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'business',
                    'setting_key' => 'check_in_time',
                    'setting_value' => '14:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'business',
                    'setting_key' => 'check_out_time',
                    'setting_value' => '12:00',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'portal',
                    'setting_key' => 'tagline',
                    'setting_value' => 'Hotel city stay yang nyaman untuk business trip maupun family staycation.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'portal',
                    'setting_key' => 'hero_title',
                    'setting_value' => 'Discover your stay at Booking WPA Hotel',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'portal',
                    'setting_key' => 'hero_description',
                    'setting_value' => 'Scan portal ini untuk melihat fasilitas hotel, kamar yang tersedia hari ini, dan rekomendasi kamar terbaik untuk kebutuhan Anda.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => null,
                    'setting_group' => 'ui',
                    'setting_key' => 'primary_color',
                    'setting_value' => '#2563eb',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => null,
                    'setting_group' => 'ui',
                    'setting_key' => 'layout_mode',
                    'setting_value' => 'sidebar',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => null,
                    'setting_group' => 'ui',
                    'setting_key' => 'sidebar_collapsed',
                    'setting_value' => '0',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => null,
                    'setting_group' => 'ui',
                    'setting_key' => 'table_density',
                    'setting_value' => 'comfortable',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ], ['property_id', 'setting_group', 'setting_key'], ['setting_value', 'updated_at']);

            RoomType::query()->insert([
                [
                    'property_id' => $propertyId,
                    'code' => 'SUP',
                    'name' => 'Superior Room',
                    'capacity' => 2,
                    'base_price' => 520000,
                    'weekend_price' => 590000,
                    'extra_bed_price' => 120000,
                    'description' => 'Pilihan efisien untuk business trip dengan layout ringkas dan smart workspace.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'code' => 'DLX',
                    'name' => 'Deluxe Room',
                    'capacity' => 2,
                    'base_price' => 690000,
                    'weekend_price' => 760000,
                    'extra_bed_price' => 150000,
                    'description' => 'Kamar paling populer untuk pasangan dan tamu corporate dengan ruang lebih lega.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'code' => 'FAM',
                    'name' => 'Family Suite',
                    'capacity' => 4,
                    'base_price' => 1180000,
                    'weekend_price' => 1290000,
                    'extra_bed_price' => 0,
                    'description' => 'Suite luas untuk keluarga kecil dengan sofa lounge dan dining corner.',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            $roomTypes = RoomType::query()
                ->where('property_id', $propertyId)
                ->pluck('id', 'code');

            Room::query()->insert([
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['SUP'],
                    'room_number' => '101',
                    'floor' => 1,
                    'current_status' => 'available',
                    'housekeeping_status' => 'clean',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['SUP'],
                    'room_number' => '102',
                    'floor' => 1,
                    'current_status' => 'occupied',
                    'housekeeping_status' => 'clean',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['DLX'],
                    'room_number' => '103',
                    'floor' => 1,
                    'current_status' => 'dirty',
                    'housekeeping_status' => 'dirty',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['DLX'],
                    'room_number' => '201',
                    'floor' => 2,
                    'current_status' => 'available',
                    'housekeeping_status' => 'clean',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['FAM'],
                    'room_number' => '301',
                    'floor' => 3,
                    'current_status' => 'available',
                    'housekeeping_status' => 'clean',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            PropertyFacility::query()->insert([
                [
                    'property_id' => $propertyId,
                    'name' => 'Infinity Pool',
                    'icon' => 'mdi:pool',
                    'description' => 'Kolam renang rooftop dengan view city skyline.',
                    'display_order' => 1,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'All-day Dining',
                    'icon' => 'mdi:silverware-fork-knife',
                    'description' => 'Sarapan buffet dan menu ala carte sepanjang hari.',
                    'display_order' => 2,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'High Speed Wi-Fi',
                    'icon' => 'mdi:wifi',
                    'description' => 'Internet cepat untuk kerja remote dan streaming.',
                    'display_order' => 3,
                    'is_featured' => true,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'Airport Transfer',
                    'icon' => 'mdi:car-convertible',
                    'description' => 'Layanan penjemputan bandara dengan reservasi front desk.',
                    'display_order' => 4,
                    'is_featured' => false,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            DB::table('housekeeping_tasks')->insert([
                'property_id' => $propertyId,
                'room_id' => Room::query()->where('room_number', '103')->value('id'),
                'task_type' => 'checkout_cleaning',
                'priority' => 'high',
                'task_status' => 'pending',
                'scheduled_for' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('inventory_categories')->insert([
                'property_id' => $propertyId,
                'name' => 'Amenities',
                'code' => 'AMENITIES',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $categoryId = DB::table('inventory_categories')->where('code', 'AMENITIES')->value('id');

            DB::table('inventory_items')->insert([
                'property_id' => $propertyId,
                'category_id' => $categoryId,
                'sku' => 'AM-0001',
                'item_name' => 'Toothbrush Kit',
                'unit' => 'pcs',
                'minimum_stock' => 20,
                'current_stock' => 12,
                'last_purchase_price' => 3500,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoices')->insert([
                'invoice_number' => 'INV-0001',
                'invoice_status' => 'partial',
                'issued_at' => now(),
                'subtotal_amount' => 850000,
                'tax_amount' => 93500,
                'service_amount' => 0,
                'discount_amount' => 0,
                'grand_total' => 943500,
                'paid_amount' => 400000,
                'remaining_amount' => 543500,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
