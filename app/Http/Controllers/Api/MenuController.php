<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

class MenuController extends Controller
{
    public function index()
    {
        // 撈出分類，並連同底下的商品(products)一起抓出來
        $menu = Category::with('products')
            ->orderBy('sort_order')
            ->get();

        return response()->json($menu);
    }
}