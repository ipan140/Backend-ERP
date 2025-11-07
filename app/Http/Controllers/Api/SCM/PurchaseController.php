<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{Purchase, PurchaseItem, Vendor, Item, StockMove, StockLevel, Location};

class PurchaseController extends Controller
{
    public function index(Request $r)
    {
        $q = Purchase::with('vendor:id,name');
        if ($r->filled('status')) $q->where('status', $r->status);
        return response()->json($q->orderByDesc('id')->paginate(20));
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'vendor_id'        => 'required|integer|exists:vendors,id',
            'order_date'       => 'required|date',
            'planned_location' => 'nullable|integer|exists:locations,id',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|integer|exists:items,id',
            'items.*.qty'      => 'required|numeric|min:0.0001',
            'items.*.uom'      => 'required|string|max:20',
            'items.*.price'    => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($data) {
            $po = Purchase::create([
                'vendor_id'        => $data['vendor_id'],
                'order_date'       => $data['order_date'],
                'planned_location' => $data['planned_location'] ?? null,
                'status'           => 'draft',
            ]);

            foreach ($data['items'] as $row) {
                PurchaseItem::create([
                    'purchase_id' => $po->id,
                    'item_id'     => $row['item_id'],
                    'qty'         => $row['qty'],
                    'uom'         => $row['uom'],
                    'price'       => $row['price'],
                ]);
            }

            return response()->json(['ok'=>true,'purchase'=>$po->load('items')], 201);
        });
    }

    public function show($id)
    {
        $po = Purchase::with(['items.item','vendor'])->findOrFail($id);
        return response()->json(['ok'=>true,'purchase'=>$po]);
    }

    public function update(Request $r, $id)
    {
        $po = Purchase::findOrFail($id);
        $data = $r->validate([
            'order_date' => 'nullable|date',
            'status'     => 'nullable|in:draft,confirmed,received,cancelled',
        ]);
        $po->update($data);
        return response()->json(['ok'=>true,'purchase'=>$po]);
    }

    public function destroy($id)
    {
        $po = Purchase::findOrFail($id);
        $po->delete();
        return response()->json(['ok'=>true]);
    }

    public function confirm($id)
    {
        $po = Purchase::findOrFail($id);
        if ($po->status !== 'draft') {
            return response()->json(['ok'=>false,'message'=>'Only draft can be confirmed'], 422);
        }
        $po->update(['status'=>'confirmed']);
        return response()->json(['ok'=>true,'message'=>'Purchase confirmed']);
    }

    // Receive → IN ke planned_location (jika ada)
    public function receive(Request $r, $id)
    {
        $po = Purchase::with('items')->findOrFail($id);
        if (! $po->planned_location) {
            return response()->json(['ok'=>false,'message'=>'planned_location is required on PO'], 422);
        }

        if (! in_array($po->status, ['confirmed','draft'])) {
            return response()->json(['ok'=>false,'message'=>'PO must be draft/confirmed'], 422);
        }

        DB::transaction(function () use ($po) {
            foreach ($po->items as $pi) {
                StockMove::create([
                    'item_id'          => $pi->item_id,
                    'from_location_id' => null,
                    'to_location_id'   => $po->planned_location,
                    'qty'              => $pi->qty,
                    'uom'              => $pi->uom,
                    'type'             => 'in',
                    'ref'              => 'po#'.$po->id,
                    'moved_at'         => now(),
                ]);

                $lvl = StockLevel::firstOrCreate(
                    ['item_id'=>$pi->item_id,'location_id'=>$po->planned_location],
                    ['qty'=>0]
                );
                $lvl->increment('qty', $pi->qty);
            }

            $po->update(['status'=>'received']);
        });

        return response()->json(['ok'=>true,'message'=>'PO received & stock updated']);
    }
}
