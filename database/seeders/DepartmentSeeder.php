<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        // Hapus dulu (aman FK)
        DB::table('departments')->delete();
        DB::unprepared('ALTER TABLE departments AUTO_INCREMENT = 1');

        $hq = Department::create(['code'=>'HQ','name'=>'Head Quarter','active'=>true]);
        Department::create(['code'=>'HR','name'=>'Human Resources','parent_id'=>$hq->id,'active'=>true]);
        Department::create(['code'=>'IT','name'=>'Information Technology','parent_id'=>$hq->id,'active'=>true]);
        Department::create(['code'=>'ACC','name'=>'Accounting','parent_id'=>$hq->id,'active'=>true]);
    }
}
