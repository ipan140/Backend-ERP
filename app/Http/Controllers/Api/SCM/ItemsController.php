<?php

namespace App\Http\Controllers\SCM;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
    public function index()
    {
        $items = Item::select('id', 'name')->orderBy('name')->get();

        return response()->json([
            'data' => $items
        ]);
    }
}
