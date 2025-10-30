<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\{Payslip, PayslipLine, Employee};

class PayslipSeeder extends Seeder
{
    public function run(): void
    {
        // 🔒 Matikan FK check biar aman saat kosongkan tabel ber-relasi
        try { DB::statement('SET FOREIGN_KEY_CHECKS=0'); } catch (\Throwable $e) {}

        // HAPUS CHILD DULU BARU PARENT (hindari error 1701)
        // Gunakan delete() agar aman di semua engine; truncate boleh kalau FK off.
        PayslipLine::query()->delete();
        Payslip::query()->delete();

        try { DB::statement('SET FOREIGN_KEY_CHECKS=1'); } catch (\Throwable $e) {}

        // Ambil karyawan; kalau belum ada, buat minimal agar seeder tetap jalan
        $emp = Employee::where('emp_no', 'EMP002')->first();
        if (!$emp) {
            $emp = Employee::first(); // fallback: siapa saja
        }
        if (!$emp) {
            // Jika sama sekali belum ada employee, hentikan dengan info jelas
            $this->command?->warn('⚠️  PayslipSeeder: Tidak ada employee. Jalankan EmployeeSeeder dulu.');
            return;
        }

        // Buat payslip dasar (APPROVED)
        $p = Payslip::create([
            'employee_id'   => $emp->id,
            'period_start'  => '2025-06-01',
            'period_end'    => '2025-06-30',
            'status'        => 'approved',
            'basic_salary'  => 5_000_000,
            'notes'         => 'Payroll June 2025',
            'approved_by'   => $emp->manager_id, // boleh null kalau tidak ada manager
            'approved_at'   => now(),
        ]);

        // Lines (earning + deduction)
        $lines = [
            [
                'payslip_id' => $p->id,
                'seq'        => 1,
                'code'       => 'ALW_MEAL',
                'name'       => 'Meal Allowance',
                'type'       => 'earning',
                'qty'        => 20,
                'rate'       => 15000,
                'amount'     => 300000,
            ],
            [
                'payslip_id' => $p->id,
                'seq'        => 2,
                'code'       => 'BONUS',
                'name'       => 'Bonus',
                'type'       => 'earning',
                'qty'        => null,
                'rate'       => null,
                'amount'     => 500000,
            ],
            [
                'payslip_id' => $p->id,
                'seq'        => 3,
                'code'       => 'DED_BPJS',
                'name'       => 'BPJS',
                'type'       => 'deduction',
                'qty'        => null,
                'rate'       => null,
                'amount'     => 150000,
            ],
        ];
        PayslipLine::insert($lines);

        // Recalc total + tandai dibayar
        $p->load('lines');
        // Jika method kamu: recalcTotals(bool $persist = false)
        if (method_exists($p, 'recalcTotals')) {
            try {
                $p->recalcTotals(true); // hitung & simpan
            } catch (\ArgumentCountError $e) {
                // fallback untuk versi tanpa argumen
                $p->recalcTotals();
                $p->save();
            }
        }
        $p->status    = 'paid';
        $p->posted_at = now();
        $p->save();
    }
}
