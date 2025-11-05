<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Vendor; // ✅ ganti jadi App\Models\Vendor, bukan App\Models\SCM\Vendor

class VendorSeeder extends Seeder
{
    public function run(): void
    {
        if (Vendor::count() === 0) {
            Vendor::insert([
                [
                    'code'      => 'SUP-001',
                    'name'      => 'PT Maju Jaya Sentosa',
                    'email'     => 'sales@majujaya.co.id',
                    'phone'     => '021-555-1234',
                    'address'   => 'Jl. Industri No.1, Jakarta',
                ],
                [
                    'code'      => 'SUP-002',
                    'name'      => 'CV Sumber Rejeki',
                    'email'     => 'halo@sumberrejeki.id',
                    'phone'     => '031-777-888',
                    'address'   => 'Jl. Kenjeran No.77, Surabaya',
                ],
            ]);
        }
    }
}
