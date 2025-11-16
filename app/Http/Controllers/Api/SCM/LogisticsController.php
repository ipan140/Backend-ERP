<?php

namespace App\Http\Controllers\Api\SCM;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\{
    Shipment,
    ShipmentItem,
    StockMove,
    StockLevel,
    Location,
    Item,
    Vendor
};

class LogisticsController extends Controller
{
    /**
     * LIST SHIPMENTS (WITH PAGINATION)
     */
    public function index(Request $r)
    {
        $q = Shipment::with('vendor:id,name');

        if ($r->filled('status')) {
            $q->where('status', $r->status);
        }

        $res = $q->orderByDesc('id')->paginate(15);

        return response()->json([
            'data' => $res->items(),
            'meta' => [
                'current_page' => $res->currentPage(),
                'per_page'     => $res->perPage(),
                'total'        => $res->total(),
                'last_page'    => $res->lastPage(),
            ]
        ]);
    }

    /**
     * CREATE SHIPMENT
     */
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

        if (!$data['from_location_id'] && !$data['to_location_id']) {
            return response()->json([
                'ok' => false,
                'message' => 'from_location_id atau to_location_id harus diisi'
            ], 422);
        }

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

            return response()->json([
                'ok' => true,
                'data' => $shipment->load('items.item', 'vendor')
            ], 201);
        });
    }

    /**
     * SHOW DETAIL SHIPMENT
     */
    public function show($id)
    {
        $ship = Shipment::with(['items.item', 'vendor'])->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => $ship
        ]);
    }

    /**
     * UPDATE SHIPMENT (ONLY DRAFT)
     */
    public function update(Request $r, $id)
    {
        $ship = Shipment::findOrFail($id);

        if ($ship->status !== 'draft') {
            return response()->json([
                'ok' => false,
                'message' => 'Only draft shipments can be edited'
            ], 422);
        }

        $data = $r->validate([
            'date'   => 'nullable|date',
            'status' => 'nullable|in:draft,confirmed,done,cancelled',
        ]);

        $ship->update($data);

        return response()->json([
            'ok' => true,
            'data' => $ship
        ]);
    }

    /**
     * DELETE SHIPMENT
     */
    public function destroy($id)
    {
        $ship = Shipment::findOrFail($id);

        if ($ship->status !== 'draft') {
            return response()->json([
                'ok' => false,
                'message' => 'Only draft shipments can be deleted'
            ], 422);
        }

        $ship->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * CONFIRM SHIPMENT
     */
    public function confirm($id)
    {
        $ship = Shipment::findOrFail($id);

        if ($ship->status !== 'draft') {
            return response()->json([
                'ok' => false,
                'message' => 'Only draft can be confirmed'
            ], 422);
        }

        $ship->update(['status' => 'confirmed']);

        return response()->json([
            'ok' => true,
            'message' => 'Shipment confirmed'
        ]);
    }

    /**
     * PROOF OF DELIVERY → MOVE STOCK & SET STATUS TO DONE
     */
    public function proofOfDelivery(Request $r, $id)
    {
        $ship = Shipment::with('items')->findOrFail($id);

        if ($ship->status !== 'confirmed') {
            return response()->json([
                'ok' => false,
                'message' => 'Shipment must be confirmed first'
            ], 422);
        }

        DB::transaction(function () use ($ship) {

            foreach ($ship->items as $si) {

                /**
                 * OUTBOUND
                 */
                if ($ship->from_location_id) {

                    // prevent negative stock
                    $current = StockLevel::firstOrCreate(
                        ['item_id' => $si->item_id, 'location_id' => $ship->from_location_id],
                        ['qty' => 0]
                    );

                    if ($current->qty < $si->qty) {
                        throw new \Exception("Stock tidak cukup di location #{$ship->from_location_id}");
                    }

                    StockMove::create([
                        'item_id'          => $si->item_id,
                        'from_location_id' => $ship->from_location_id,
                        'to_location_id'   => null,
                        'qty'              => $si->qty,
                        'uom'              => $si->uom,
                        'type'             => 'out',
                        'ref'              => 'shipment#' . $ship->id,
                        'moved_at'         => now(),
                    ]);

                    $current->decrement('qty', $si->qty);
                }

                /**
                 * INBOUND
                 */
                if ($ship->to_location_id) {
                    StockMove::create([
                        'item_id'          => $si->item_id,
                        'from_location_id' => null,
                        'to_location_id'   => $ship->to_location_id,
                        'qty'              => $si->qty,
                        'uom'              => $si->uom,
                        'type'             => 'in',
                        'ref'              => 'shipment#' . $ship->id,
                        'moved_at'         => now(),
                    ]);

                    $lvl = StockLevel::firstOrCreate(
                        ['item_id' => $si->item_id, 'location_id' => $ship->to_location_id],
                        ['qty' => 0]
                    );

                    $lvl->increment('qty', $si->qty);
                }
            }

            $ship->update(['status' => 'done']);
        });

        return response()->json([
            'ok' => true,
            'message' => 'POD recorded & stock moved'
        ]);
    }
}
