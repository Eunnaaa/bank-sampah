<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Tenant;
use App\Models\WasteCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('Seeder demo hanya boleh dijalankan di lingkungan local atau testing.');
        }

        // 1. Unit RT/RW Lingkungan
        $tenant = Tenant::create([
            'id' => Str::uuid(),
            'code' => 'bs-rt05-rw08',
            'name' => 'Bank Sampah Warga RT 05 / RW 08',
            'rt' => '05',
            'rw' => '08',
            'city' => 'Jakarta',
            'status' => 'active',
        ]);

        // 2. Akun Pengurus Resmi (Admin & Petugas)
        Profile::create([
            'id' => '20000000-0000-4000-8000-000000000010',
            'tenant_id' => $tenant->id,
            'full_name' => 'Gary Hardyansyah',
            'phone_number' => '081234567890',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok B · No. 10',
            'role' => 'admin',
            'balance' => 0,
            'password_hash' => Hash::make('admin123456'),
        ]);

        Profile::create([
            'id' => '20000000-0000-4000-8000-000000000020',
            'tenant_id' => $tenant->id,
            'full_name' => 'Pak RT Slamet',
            'phone_number' => '081298765432',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok A · No. 01',
            'role' => 'officer',
            'balance' => 0,
            'password_hash' => Hash::make('petugas123456'),
        ]);

        Profile::create([
            'id' => '20000000-0000-4000-8000-000000000030',
            'tenant_id' => $tenant->id,
            'full_name' => 'Keluarga Budi Santoso',
            'phone_number' => '081999888777',
            'rt' => '05',
            'rw' => '08',
            'house_number' => 'Blok C No. 05',
            'role' => 'resident',
            'balance' => 0,
            'pin_hash' => Hash::make('1234'),
        ]);

        // 3. Katalog Komoditas Aktif
        $categories = [
            ['name' => 'Plastik PET (Botol/Gelas Bening)', 'unit' => 'kg', 'price_per_unit' => 3500],
            ['name' => 'Kardus & Kertas Campur', 'unit' => 'kg', 'price_per_unit' => 2200],
            ['name' => 'Besi & Logam Daur Ulang', 'unit' => 'kg', 'price_per_unit' => 4500],
            ['name' => 'Minyak Jelantah Rumah Tangga', 'unit' => 'liter', 'price_per_unit' => 6000],
        ];

        foreach ($categories as $cat) {
            WasteCategory::create([
                'id' => Str::uuid(),
                'tenant_id' => $tenant->id,
                'name' => $cat['name'],
                'unit' => $cat['unit'],
                'price_per_unit' => $cat['price_per_unit'],
                'is_active' => true,
            ]);
        }
    }
}
