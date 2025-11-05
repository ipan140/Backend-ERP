<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Item;
use App\Models\Warehouse;

class ShipmentItemSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::count() === 0) {
            Item::insert([
                ['sku'=>'ITM-001','name'=>'Pupuk NPK','unit'=>'kg','price'=>250000],
                ['sku'=>'ITM-002','name'=>'Bibit Tomat','unit'=>'pack','price'=>45000],
                ['sku'=>'ITM-003','name'=>'Pestisida','unit'=>'liter','price'=>85000],
            ]);
        }
        if (Warehouse::count() === 0) {
            Warehouse::insert([
                ['code'=>'WH-01','name'=>'Gudang Pusat'],
                ['code'=>'WH-02','name'=>'Gudang Cabang'],
            ]);
        }

        $shipments = Shipment::get();
        if ($shipments->isEmpty()) return;

        foreach ($shipments as $sh) {
            DB::transaction(function () use ($sh) {
                $items = Item::inRandomOrder()->take(2)->get();
                $whId  = Warehouse::inRandomOrder()->value('id');

                foreach ($items as $it) {
                    ShipmentItem::create([
                        'shipment_id'  => $sh->id,
                        'item_id'      => $it->id,
                        'warehouse_id' => $whId,      // kalau kolom ini tidak ada, hapus baris ini
                        'qty'          => rand(5,15),
                        'notes'        => null,
                    ]);
                }
            });
        }
    }
}
