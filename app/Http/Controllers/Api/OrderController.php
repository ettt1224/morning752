<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\OrderItem;
use App\Events\NewOrderEvent; // 記得引入這個事件

class OrderController extends Controller
{
    // 1. 取得訂單列表 (對應 GET /api/orders)
    public function index()
    {
        // 撈出狀態是 pending (等待中) 的訂單，並且連同 items (細項) 一起抓出來
        // 依照時間「舊到新」排序 (越早點的要越先做)
        $orders = Order::with('items')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($orders);
    }

    // 2. 建立新訂單 (對應 POST /api/orders)
    public function store(Request $request)
    {
        // 使用 Transaction (交易) 確保資料完整性
        // 萬一寫入細項失敗，主訂單也會自動取消
        try {
            DB::beginTransaction();

            // A. 建立訂單主檔
            $order = Order::create([
                'order_number' => 'ORD-' . time(), // 簡單的訂單編號
                'type' => $request->type ?? '內用', // 如果沒傳 type，預設內用
                'total_amount' => $request->total,
                'status' => 'pending'
            ]);

            // B. 建立訂單細項
            foreach ($request->items as $item) {
                // 💡 修正重點在這裡：
                // 前端傳來的 ID 可能是 "5-hot"，我們用 intval() 強制轉成數字 5
                // PHP 的 intval("5-hot") 會自動變成 5，剛好解決問題

                $productId = intval($item['id']);

                // 這裡我們把 "(冰)" 或 "(熱)" 抓出來，存到 options 欄位 (如果有的話)
                // 或者是直接存進 product_name 也可以，看你的前端怎麼傳

                $order->items()->create([
                    'product_id' => $productId, // ✅ 這裡只存純數字
                    'product_name' => $item['name'], // 這裡存 "珍珠奶茶 (冰)"
                    'price' => $item['price'],
                    'quantity' => $item['qty'],
                    // 如果你的 order_items 表有 options 欄位，可以這樣存：
                    'options' => str_contains($item['id'], '-') ? (str_contains($item['id'], 'cold') ? '冰' : '熱') : null,
                ]);
            }

            DB::commit(); // 確認寫入資料庫

            // C. 【關鍵】觸發廣播通知廚房
            // 必須在 commit 之後做，確保資料庫已經有資料了
            NewOrderEvent::dispatch($order);

            // D. 回傳成功訊息
            return response()->json([
                'message' => '訂單建立成功！',
                'order_id' => $order->id
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // 發生錯誤，復原所有動作
            // 回傳 500 錯誤碼，讓前端知道失敗了
            return response()->json([
                'error' => '訂單建立失敗',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function toggleItemStatus($id)
    {
        // 1. 找到該細項
        $item = OrderItem::findOrFail($id);

        // 2. 切換狀態 (如果原本是完成就變未完成，反之亦然)
        $item->is_done = !$item->is_done;
        $item->save();

        // 3. 回傳最新的狀態給前端
        return response()->json([
            'message' => '狀態更新成功',
            'is_done' => $item->is_done
        ]);
    }
}