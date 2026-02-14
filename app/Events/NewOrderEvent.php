<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast; // 1. 記得引入這行
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

// 2. 這裡一定要加上 implements ShouldBroadcast (代表這個事件是要廣播出去的)
class NewOrderEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order; // 定義一個變數，Laravel 會自動把這個變數轉成 JSON 傳給前端

    public function __construct(Order $order)
    {
        $this->order = $order;
        // 為了讓前端方便顯示，我們順便把細項也載入進來
        $this->order->load('items');
    }

    public function broadcastOn(): array
    {
        // 3. 定義頻道名稱，我們叫它 'kitchen' (廚房頻道)
        return [
            new Channel('kitchen'),
        ];
    }
}