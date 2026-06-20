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

            $adminUser = User::factory()->create([
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
                    'setting_key' => 'current_business_date',
                    'setting_value' => now('Asia/Jakarta')->toDateString(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'setting_group' => 'business',
                    'setting_key' => 'night_audit_cutoff_time',
                    'setting_value' => '02:00',
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
                    'size_sqm' => 22,
                    'bed_type' => 'Queen',
                    'smoking_allowed' => false,
                    'amenities' => 'WiFi,AC,TV,Kamar Mandi Dalam,Smart Workspace',
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
                    'size_sqm' => 28,
                    'bed_type' => 'King',
                    'smoking_allowed' => false,
                    'amenities' => 'WiFi,AC,TV,Kamar Mandi Dalam,Bathtub,Mini Bar',
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
                    'size_sqm' => 45,
                    'bed_type' => 'King + Single',
                    'smoking_allowed' => false,
                    'amenities' => 'WiFi,AC,TV,Kamar Mandi Dalam,Bathtub,Mini Bar,Sofa Lounge,Dining Corner',
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
                    'serviceability_status' => 'normal',
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
                    'serviceability_status' => 'normal',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'room_type_id' => $roomTypes['DLX'],
                    'room_number' => '103',
                    'floor' => 1,
                    'current_status' => 'available',
                    'housekeeping_status' => 'dirty',
                    'serviceability_status' => 'normal',
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
                    'serviceability_status' => 'normal',
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
                    'serviceability_status' => 'normal',
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

            // Create a sample guest & reservation to link invoice
            $sampleGuestId = DB::table('guests')->insertGetId([
                'property_id' => $propertyId,
                'full_name' => 'Budi Santoso',
                'phone' => '+62 812-3456-7890',
                'email' => 'budi@example.com',
                'id_type' => 'KTP',
                'id_number' => '3174123456789012',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sampleRoomId = Room::query()->where('room_number', '102')->value('id');

            $sampleReservationId = DB::table('reservations')->insertGetId([
                'property_id' => $propertyId,
                'primary_guest_id' => $sampleGuestId,
                'room_type_id' => $roomTypes['SUP'],
                'assigned_room_id' => $sampleRoomId,
                'booking_code' => 'BK-'.now()->format('Ymd').'-SAMPLE',
                'source' => 'online_portal',
                'reservation_status' => 'checked_in',
                'adult_count' => 1,
                'child_count' => 0,
                'check_in_date' => now()->subDays(1)->toDateString(),
                'check_out_date' => now()->toDateString(),
                'payment_status' => 'partial',
                'guarantee_status' => 'booking',
                'deposit_amount' => 400000,
                'booked_at' => now()->subDays(2),
                'checked_in_at' => now()->subDays(1),
                'created_by_user_id' => $adminUser->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('invoices')->insert([
                'reservation_id' => $sampleReservationId,
                'invoice_number' => 'INV-0001',
                'invoice_status' => 'partial',
                'issued_at' => now()->subDays(1),
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
            // ────────────────────────────────────────────
            //  Loanable Assets (for guest portal)
            // ────────────────────────────────────────────
            DB::table('loanable_assets')->insert([
                [
                    'property_id' => $propertyId,
                    'name' => 'Remote TV',
                    'description' => 'Remote kontrol TV untuk kamar.',
                    'total_stock' => 15,
                    'available_stock' => 13,
                    'condition_notes' => 'Baik',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'Adaptor Universal',
                    'description' => 'Adaptor colokan universal untuk perangkat elektronik.',
                    'total_stock' => 10,
                    'available_stock' => 10,
                    'condition_notes' => 'Baru',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'Bantal Extra',
                    'description' => 'Bantal ekstra untuk kenyamanan tambahan.',
                    'total_stock' => 20,
                    'available_stock' => 18,
                    'condition_notes' => 'Baik',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'property_id' => $propertyId,
                    'name' => 'Setrika & Papan Setrika',
                    'description' => 'Setrika listrik dengan papan setrika.',
                    'total_stock' => 5,
                    'available_stock' => 5,
                    'condition_notes' => 'Baik',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

            // Create an asset loan for the sample reservation
            $remoteTvId = DB::table('loanable_assets')->where('name', 'Remote TV')->value('id');
            DB::table('asset_loans')->insert([
                'reservation_id' => $sampleReservationId,
                'asset_id' => $remoteTvId,
                'staff_id' => $adminUser->id,
                'loaned_at' => now()->subDay(),
                'returned_at' => null,
                'return_condition' => null,
                'charge_amount' => null,
                'notes' => 'Request from guest for TV remote.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Update available stock for loaned asset
            DB::table('loanable_assets')->where('id', $remoteTvId)->decrement('available_stock');

            // Create a room condition report for the sample reservation
            DB::table('room_condition_reports')->insert([
                'reservation_id' => $sampleReservationId,
                'room_id' => $sampleRoomId,
                'reported_by' => null,
                'reporter_type' => 'guest',
                'guest_name' => 'Budi Santoso',
                'report_time' => now()->subMinutes(30),
                'window_expired_at' => now()->addMinutes(30),
                'items' => json_encode([
                    ['category' => 'Elektronik', 'description' => 'Remote TV tidak berfungsi dengan baik.'],
                    ['category' => 'Kebersihan', 'description' => 'Ada noda di seprai.'],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }
}
