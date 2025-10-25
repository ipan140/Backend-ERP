<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $req)
    {
        $q = Invoice::query()->with('customer');

        // filtering
        if ($status = $req->string('status')->toString()) {
            $q->whereIn('status', array_map('trim', explode(',', $status)));
        }
        if ($kw = $req->string('q')->toString()) {
            $q->where(function ($w) use ($kw) {
                $w->where('number', 'like', "%$kw%")
                  ->orWhereHas('customer', fn($c) => $c->where('name', 'like', "%$kw%"));
            });
        }
        if ($df = $req->date('date_from')) $q->whereDate('created_at', '>=', $df);
        if ($dt = $req->date('date_to'))   $q->whereDate('created_at', '<=', $dt);

        // sorting
        if ($sort = $req->string('sort')->toString()) {
            foreach (explode(',', $sort) as $srt) {
                $dir = str_starts_with($srt, '-') ? 'desc' : 'asc';
                $col = ltrim($srt, '-');
                if (in_array($col, ['created_at','grand_total','number'])) {
                    $q->orderBy($col, $dir);
                }
            }
        } else {
            $q->orderByDesc('id');
        }

        $perPage = (int)($req->input('per_page', 15));
        return response()->json($q->paginate($perPage));
    }

    public function show(int $id)
    {
        $invoice = Invoice::with(['items.product','customer','order'])->findOrFail($id);
        return response()->json($invoice);
    }

    public function post(int $id)
    {
        $invoice = Invoice::findOrFail($id);
        if ($invoice->status !== 'draft') {
            return response()->json(['message' => 'Only draft invoices can be posted.'], 422);
        }
        $invoice->update(['status' => 'posted', 'posted_at' => now()]);
        return response()->json($invoice);
    }

    public function pay(Request $req, int $id)
    {
        // Untuk contoh: anggap langsung lunas
        $invoice = Invoice::findOrFail($id);
        if (! in_array($invoice->status, ['posted','partial'])) {
            return response()->json(['message' => 'Invoice must be posted or partial to pay.'], 422);
        }
        $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        return response()->json([
            'message' => 'Payment recorded (example).',
            'data'    => $invoice
        ]);
    }
}
