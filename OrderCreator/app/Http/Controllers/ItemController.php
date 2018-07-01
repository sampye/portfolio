<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Item;

class ItemController extends Controller
{
    public function getIndex()
    {
        $items = Item::all();
        return view('content/items')
            ->with('items', $items);
    }
}
