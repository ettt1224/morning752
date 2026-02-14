<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * 顯示商品列表
     */
    public function index()
    {
        return Product::with('category')->orderBy('created_at', 'desc')->get();
    }

    /**
     * 新增商品 (Create)
     */
    public function store(Request $request)
    {
        // 1. 驗證資料
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|integer',
            'category_name' => 'required|string|max:50',
            'image' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            // 👇 新增：驗證冷熱選項
            'has_cold_hot' => 'boolean'
        ]);

        // 2. 處理分類
        $category = Category::firstOrCreate(
            ['name' => $request->category_name],
            ['sort_order' => 0]
        );

        // 3. 處理圖片上傳
        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
        }

        // 4. 寫入資料庫
        $product = Product::create([
            'name' => $validated['name'],
            'price' => $validated['price'],
            'category_id' => $category->id,
            'description' => $request->description ?? null,
            'image' => $path ? '/storage/' . $path : null,
            'is_enabled' => true,
            // 👇 新增：儲存冷熱選項 (強制轉為布林值)
            'has_cold_hot' => $request->boolean('has_cold_hot')
        ]);

        return response()->json(['message' => '商品新增成功！', 'product' => $product], 201);
    }

    /**
     * 顯示單一商品
     */
    public function show($id)
    {
        return Product::findOrFail($id);
    }

    /**
     * 更新商品 (Update)
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        // 1. 驗證
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'price' => 'sometimes|required|integer',
            'category_name' => 'sometimes|required|string|max:50',
            'image' => 'nullable|image|max:2048',
            'description' => 'nullable|string',
            'is_enabled' => 'boolean',
            // 👇 新增：驗證冷熱選項
            'has_cold_hot' => 'boolean'
        ]);

        // 2. 處理分類
        if ($request->has('category_name')) {
            $category = Category::firstOrCreate(['name' => $request->category_name]);
            $product->category_id = $category->id;
        }

        // 3. 處理圖片更新
        if ($request->hasFile('image')) {
            if ($product->image) {
                $oldPath = str_replace('/storage/', '', $product->image);
                Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image')->store('products', 'public');
            $product->image = '/storage/' . $path;
        }

        // 4. 更新其他欄位
        // 排除不屬於 products 表格原本欄位的參數
        $dataToUpdate = $request->except(['image', 'category_name']);
        
        // 確保 has_cold_hot 如果有傳入，會被正確更新
        // (Laravel update 會自動處理 1/0 轉 boolean，但為了保險起見，我們手動覆蓋它)
        if ($request->has('has_cold_hot')) {
            $product->has_cold_hot = $request->boolean('has_cold_hot');
        }

        $product->update($dataToUpdate);
        $product->save(); 

        return response()->json(['message' => '商品更新成功！', 'product' => $product]);
    }

    /**
     * 刪除商品
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        if ($product->image) {
            $oldPath = str_replace('/storage/', '', $product->image);
            Storage::disk('public')->delete($oldPath);
        }

        $product->delete();

        return response()->json(['message' => '商品已刪除']);
    }
}