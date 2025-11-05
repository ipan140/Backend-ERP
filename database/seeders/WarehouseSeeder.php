<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Warehouse;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        if (Warehouse::count() === 0) {
            Warehouse::insert([
                [
                    'code' => 'WH-01',
                    'name' => 'Gudang Utama Surabaya',
                    'location' => 'Jl. Rungkut Industri I No. 12, Surabaya',
                ],
                [
                    'code' => 'WH-02',
                    'name' => 'Gudang Cabang Sidoarjo',
                    'location' => 'Jl. Raya Taman No. 5, Sidoarjo',
                ],
            ]);
        }
    }
}
