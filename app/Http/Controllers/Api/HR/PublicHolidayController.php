<?php

namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\PublicHoliday;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicHolidayController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->integer('per_page', 50);
        $perPage = $perPage > 0 ? min($perPage, 100) : 50;

        $q = PublicHoliday::query()
            ->search($request->get('search'))
            ->year($request->get('year'))
            ->month($request->get('month'))
            ->between($request->get('date_from'), $request->get('date_to'))
            ->national($request->has('is_national') ? $request->boolean('is_national') : null)
            ->orderBy('date');

        return response()->json($q->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required','string','max:150'],
            'date'        => [
                'required','date',
                // unique + kompatibel SoftDeletes
                Rule::unique('public_holidays','date')->whereNull('deleted_at'),
            ],
            'is_national' => ['nullable','boolean'],
            'note'        => ['nullable','string'],
        ]);

        // default false jika tidak dikirim
        $data['is_national'] = array_key_exists('is_national', $data) ? (bool)$data['is_national'] : false;

        $row = PublicHoliday::create($data);
        return response()->json($row, 201);
    }

    public function show($id)
    {
        $row = PublicHoliday::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        return response()->json($row);
    }

    public function update(Request $request, $id)
    {
        $row = PublicHoliday::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);

        $data = $request->validate([
            'name'        => ['required','string','max:150'],
            'date'        => [
                'required','date',
                Rule::unique('public_holidays','date')
                    ->ignore($row->id)
                    ->whereNull('deleted_at'),
            ],
            'is_national' => ['nullable','boolean'],
            'note'        => ['nullable','string'],
        ]);

        $row->update($data);

        return response()->json($row->refresh());
    }

    public function destroy($id)
    {
        $row = PublicHoliday::find($id);
        if (!$row) return response()->json(['message' => 'Not found'], 404);
        $row->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
