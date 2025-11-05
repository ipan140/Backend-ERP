<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Purchase;
use App\Models\PurchaseItem;

class PurchaseSeeder extends Seeder
{
    public function run(): void
    {
        if (Purchase::count() === 0) {
            // Purchase 1
            $po1 = Purchase::create([
                'number' => 'PO-20251105-0001',
                'vendor_id' => 1,
                'date' => now()->subDays(5),
                'status' => 'confirmed',
                'total' => 335000,
            ]);

            PurchaseItem::insert([
                [
                    'purchase_id' => $po1->id,
                    'item_id' => 1,
                    'qty' => 1,
                    'price' => 250000,
                    'subtotal' => 250000,
                ],
                [
                    'purchase_id' => $po1->id,
                    'item_id' => 3,
                    'qty' => 1,
                    'price' => 85000,
                    'subtotal' => 85000,
                ],
            ]);

            // Purchase 2
            $po2 = Purchase::create([
                'number' => 'PO-20251105-0002',
                'vendor_id' => 2,
                'date' => now()->subDays(2),
                'status' => 'draft',
                'total' => 50000,
            ]);

            PurchaseItem::create([
                'purchase_id' => $po2->id,
                'item_id' => 2,
                'qty' => 1,
                'price' => 50000,
                'subtotal' => 50000,
            ]);
        }
    }
}
