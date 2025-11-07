<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Shipment, ShipmentItem, StockMove, StockLevel, Location, Item, Vendor};

class LogisticsController extends Controller
{
    public function index(Request $r)
    {
        $q = Shipment::with('vendor:id,name');
        if ($r->filled('status')) $q->where('status', $r->status);
        return response()->json($q->orderByDesc('id')->paginate(15));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'vendor_id'        => 'nullable|integer|exists:vendors,id',
            'date'             => 'required|date',
            'from_location_id' => 'nullable|integer|exists:locations,id',
            'to_location_id'   => 'nullable|integer|exists:locations,id',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|integer|exists:items,id',
            'items.*.qty'      => 'required|numeric|min:0.0001',
            'items.*.uom'      => 'required|string|max:20',
        ]);

        return DB::transaction(function () use ($data) {
            $shipment = Shipment::create([
                'vendor_id'        => $data['vendor_id'] ?? null,
                'date'             => $data['date'],
                'from_location_id' => $data['from_location_id'] ?? null,
                'to_location_id'   => $data['to_location_id'] ?? null,
                'status'           => 'draft',
            ]);

            foreach ($data['items'] as $row) {
                ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'item_id'     => $row['item_id'],
                    'qty'         => $row['qty'],
                    'uom'         => $row['uom'],
                ]);
            }

            return response()->json(['ok'=>true,'shipment'=>$shipment->load('items')], 201);
        });
    }

    public function show($id)
    {
        $ship = Shipment::with(['items.item','vendor'])->findOrFail($id);
        return response()->json(['ok'=>true,'shipment'=>$ship]);
    }

    public function update(Request $r, $id)
    {
        $ship = Shipment::findOrFail($id);
        $data = $r->validate([
            'date'   => 'nullable|date',
            'status' => 'nullable|in:draft,confirmed,done,cancelled',
        ]);
        $ship->update($data);
        return response()->json(['ok'=>true,'shipment'=>$ship]);
    }

    public function destroy($id)
    {
        $ship = Shipment::findOrFail($id);
        $ship->delete();
        return response()->json(['ok'=>true]);
    }

    // Konfirmasi (lock edit, belum gerakkan stok)
    public function confirm($id)
    {
        $ship = Shipment::findOrFail($id);
        if ($ship->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only draft can be confirmed'], 422);
        }
        $ship->update(['status'=>'confirmed']);
        return response()->json(['ok'=>true,'message'=>'Shipment confirmed']);
    }

    // Proof of Delivery (generate StockMove sesuai arah)
    public function proofOfDelivery(Request $r, $id)
    {
        $ship = Shipment::with('items')->findOrFail($id);
        if ($ship->status !== 'confirmed') {
            return response()->json(['ok'=>false,'message'=>'Shipment must be confirmed first'], 422);
        }

        DB::transaction(function () use ($ship) {
            foreach ($ship->items as $si) {
                // arah: jika from_location ada → OUT dari from, jika to_location ada → IN ke to
                if ($ship->from_location_id) {
                    StockMove::create([
                        'item_id'          => $si->item_id,
                        'from_location_id' => $ship->from_location_id,
                        'to_location_id'   => null,
                        'qty'              => $si->qty,
                        'uom'              => $si->uom,
                        'type'             => 'out',
                        'ref'              => 'shipment#'.$ship->id,
                        'moved_at'         => now(),
                    ]);
                    $lvl = StockLevel::firstOrCreate(
                        ['item_id'=>$si->item_id,'location_id'=>$ship->from_location_id],
                        ['qty'=>0]
                    );
                    $lvl->decrement('qty', $si->qty);
                }

                if ($ship->to_location_id) {
                    StockMove::create([
                        'item_id'          => $si->item_id,
                        'from_location_id' => null,
                        'to_location_id'   => $ship->to_location_id,
                        'qty'              => $si->qty,
                        'uom'              => $si->uom,
                        'type'             => 'in',
                        'ref'              => 'shipment#'.$ship->id,
                        'moved_at'         => now(),
                    ]);
                    $lvl = StockLevel::firstOrCreate(
                        ['item_id'=>$si->item_id,'location_id'=>$ship->to_location_id],
                        ['qty'=>0]
                    );
                    $lvl->increment('qty', $si->qty);
                }
            }

            $ship->update(['status'=>'done']);
        });

        return response()->json(['ok'=>true,'message'=>'POD recorded & stock moved']);
    }
}
