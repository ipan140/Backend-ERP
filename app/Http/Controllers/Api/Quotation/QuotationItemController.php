<?php

namespace App\Http\Controllers\Api\Quotation;

use App\Http\Controllers\Controller;
use App\Models\QuotationItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class QuotationItemController extends Controller
{
    /**
     * GET /api/quotation-items?quotation_id=ID
     * Mengembalikan { data: [...] } agar cocok dengan frontend.
     */
    public function index(Request $request): JsonResponse
    {
        $qid = $request->integer('quotation_id');

        $query = QuotationItem::query()
            ->when($qid, fn($q) => $q->where('quotation_id', $qid))
            ->orderBy('id');

        // tanpa pagination, langsung array → { data: [...] }
        $items = $query->get();

        return response()->json(['data' => $items]);
    }

    /**
     * GET /api/quotations/{id}/items (fallback yang juga dipakai Vue)
     * Kembalikan payload sama: { data: [...] }
     */
    public function byQuotation(int $id): JsonResponse
    {
        $items = QuotationItem::where('quotation_id', $id)
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $items]);
    }

    /**
     * POST /api/quotation-items
     */
    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        // Hitung line_total di server juga (frontend sudah auto, tapi server tetap sumber kebenaran)
        $qty   = (float)($data['qty'] ?? 0);
        $price = (float)($data['unit_price'] ?? 0);
        $disc  = (float)($data['discount_pct'] ?? 0);
        $data['line_total'] = $qty * $price * (1 - $disc / 100);

        $item = QuotationItem::create($data);

        return response()->json($item, 201);
    }

    /**
     * PUT /api/quotation-items/{id}
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $item = QuotationItem::findOrFail($id);

        $data = $this->validated($request, $item->id);

        $qty   = (float)($data['qty'] ?? $item->qty);
        $price = (float)($data['unit_price'] ?? $item->unit_price);
        $disc  = (float)($data['discount_pct'] ?? $item->discount_pct);
        $data['line_total'] = $qty * $price * (1 - $disc / 100);

        $item->update($data);

        return response()->json($item);
    }

    /**
     * DELETE /api/quotation-items/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $item = QuotationItem::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted']);
    }

    /**
     * Validasi request; cocok dengan field di tabelmu (qty, uom, unit_price, discount_pct, line_total, dll).
     */
    protected function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'quotation_id' => ['required','integer','min:1'],
            'product_id'   => ['nullable','integer','min:1'],
            'description'  => ['nullable','string','max:255'],
            'qty'          => ['required','numeric','min:0'],
            'uom'          => ['nullable','string','max:20'],
            'unit_price'   => ['required','numeric','min:0'],
            'discount_pct' => ['nullable','numeric','min:0','max:100'],
            // frontend juga kirim line_total; biarkan tapi tidak wajib (server akan hitung ulang)
            'line_total'   => ['nullable','numeric','min:0'],
        ]);
    }
}
