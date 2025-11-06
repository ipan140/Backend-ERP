<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::updateOrCreate(
            ['id' => 1],
            [
                'name'     => 'RFC Rooftop Farming Center',
                'code'     => 'RFC',   // ⬅️ WAJIB diisi karena NOT NULL
                'currency' => 'IDR',   // ⬅️ jika kolom ini ada & NOT NULL
                'lock_date'=> null,
            ]
        );
    }
}
