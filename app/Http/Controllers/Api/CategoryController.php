<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * 取得分類列表 (給前端下拉選單或管理頁面用)
     */
    public function index()
    {
        // withCount('products') 可以幫你算出這個分類下有幾個商品
       return Category::withCount('products')->orderBy('id', 'desc')->get();
    }

    /**
     * 刪除分類
     * DELETE /api/categories/{id}
     */
public function destroy($id)
{
    $category = Category::findOrFail($id);
    $category->delete();
    return response()->json(['message' => '分類已刪除']);
}
}