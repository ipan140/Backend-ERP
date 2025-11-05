<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ResetSalesDataSeeder extends Seeder
{
    use SupportsFkSafe;

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('⚠️ Skip ResetSalesDataSeeder on production.');
            return;
        }

        $this->fkOff();

        // Urutkan CHILD → PARENT (modifikasi sesuai skema kamu)
        $tables = [
            'invoice_items',        // jika ada
            'invoices',             // FK -> sales_orders
            'sales_order_items',    // jika ada
            'sales_orders',
            'quotation_status_logs',
            'quotation_items',
            'quotations',
            'pricelist_items',      // jika ada
            'pricelists',
            'payment_terms',        // jika ada
            'products',
            'customers',
        ];

        foreach ($tables as $tbl) {
            $this->wipe($tbl); // delete() aman untuk FK
            // atau pakai $this->wipe($tbl, true); untuk truncate
        }

        $this->fkOn();
        $this->command?->info('✅ Sales data reset selesai.');
    }
}
