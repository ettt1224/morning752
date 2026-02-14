<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue'; // 1. 記得引入 onUnmounted
import axios from 'axios';

// --- 設定區 ---
const LOCAL_STORAGE_KEY = 'kitchen_completed_orders'; 
const REFRESH_INTERVAL = 1000; // ⚡ 自動刷新間隔 (毫秒)，這裡設 10 秒

// --- 變數 ---
const activeOrders = ref([]);
const showStats = ref(false);
let timer = null; // 定時器變數

// --- 1. 抓取資料 ---
const fetchOrders = async () => {
    try {
        const res = await axios.get(`/api/orders`);
        
        // 取得本地已完成紀錄
        const completedIds = JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY) || '[]');

        // 合併狀態 (保留本地端的「已完成」狀態，即使刷新也不會消失)
        activeOrders.value = res.data.map(order => {
            return {
                ...order,
                is_done: completedIds.includes(order.id)
            };
        });
    } catch (e) {
        console.error('抓取失敗 (可能是網路問題):', e);
    }
};

// --- 2. 完成訂單 (純前端) ---
const completeOrder = (order) => {
    order.is_done = true;
    const completedIds = JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY) || '[]');
    if (!completedIds.includes(order.id)) {
        completedIds.push(order.id);
        localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(completedIds));
    }
};

// --- 3. 換日清除 ---
const clearFinished = () => {
    if(!confirm('確定要清除畫面上「已完成」的訂單嗎？(這會重置本地端的完成紀錄)')) return;
    localStorage.removeItem(LOCAL_STORAGE_KEY);
    activeOrders.value = activeOrders.value.filter(o => !o.is_done);
};

// --- 4. 統計邏輯 ---
const todayStats = computed(() => {
    const todayStr = new Date().toDateString();
    const todayOrders = activeOrders.value.filter(o => 
        new Date(o.created_at).toDateString() === todayStr
    );

    let totalRevenue = 0;
    const itemCounts = {};

    todayOrders.forEach(order => {
        if (order.items && Array.isArray(order.items)) {
            order.items.forEach(item => {
                const price = Number(item.price) || 0;
                const qty = Number(item.quantity) || 0;
                const name = item.product_name || '未知商品';

                itemCounts[name] = (itemCounts[name] || 0) + qty;
                totalRevenue += price * qty;
            });
        }
    });

    const sortedItems = Object.entries(itemCounts).sort((a, b) => b[1] - a[1]);

    return {
        count: todayOrders.length,
        revenue: totalRevenue,
        items: sortedItems
    };
});

// --- 5. 匯出 CSV ---
const exportCSV = () => {
    let csvContent = "\uFEFF單號,時間,商品,數量,單價,總價,狀態\n";
    activeOrders.value.forEach(order => {
        const time = new Date(order.created_at).toLocaleTimeString();
        const status = order.is_done ? "已完成" : "製作中";
        order.items.forEach(item => {
            const price = item.price || 0;
            const row = [
                order.id, `"${time}"`, item.product_name, item.quantity, price, price * item.quantity, status
            ];
            csvContent += row.join(",") + "\n";
        });
    });
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = `廚房報表_${new Date().toISOString().slice(0,10)}.csv`;
    link.click();
};

const playSound = () => {
    const audio = new Audio('/ding.mp3'); 
    audio.play().catch(e => console.log('瀏覽器阻擋自動播放'));
};

// --- 生命週期 ---
onMounted(() => {
    // 1. 馬上抓一次
    fetchOrders();

    // 2. ⚡ 設定定時器：每 10 秒自動刷新一次 (保底機制)
    timer = setInterval(() => {
        // console.log('自動刷新中...');
        fetchOrders();
    }, REFRESH_INTERVAL);

    // 3. WebSocket 即時監聽 (如果有設定 Laravel Reverb/Pusher)
    if (window.Echo) {
        window.Echo.channel('kitchen')
            .listen('NewOrderEvent', (e) => {
                // 收到訊號後，重新抓取最新資料確保同步
                console.log('⚡ 收到新訂單訊號，立即刷新');
                fetchOrders(); 
                playSound();
            });
    }
});

// 當離開頁面時，清除定時器，避免佔用記憶體
onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div class="kitchen-container">
        
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid rgba(255,255,255,0.1); padding-bottom: 15px;">
            <h1 style="margin: 0;">👨‍🍳 廚房監控</h1>
            <div style="text-align: right;">
                <span style="font-size: 0.9em; opacity: 0.7; display: block;">今日單量</span>
                <span style="font-size: 1.8em; font-weight: bold; color: #2ecc71;">{{ todayStats.count }}</span>
            </div>
        </div>

        <div class="controls" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button @click="showStats = !showStats" style="background: #9b59b6; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                {{ showStats ? '❌ 關閉' : '📊 統計' }}
            </button>
            <button @click="clearFinished" style="background: #e67e22; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                🧹 換日清除
            </button>
            <button @click="exportCSV" style="background: #27ae60; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                📥 匯出
            </button>
        </div>

        <div v-if="showStats" style="background: white; color: #333; padding: 20px; border-radius: 8px; margin-bottom: 20px; display: flex; gap: 30px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">💰 今日營收估算</h3>
                <div style="font-size: 2.5em; font-weight: bold; color: #27ae60;">${{ todayStats.revenue }}</div>
            </div>
            <div style="flex: 2; min-width: 300px;">
                <h3 style="margin-top: 0; color: #2c3e50; border-bottom: 1px solid #eee; padding-bottom: 10px;">🔥 熱銷商品</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
                    <div v-for="(item, idx) in todayStats.items" :key="idx" style="background: #f8f9fa; padding: 8px; border-radius: 4px; display: flex; justify-content: space-between;">
                        <span>{{ item[0] }}</span>
                        <span style="font-weight: bold; color: #e74c3c;">x{{ item[1] }}</span>
                    </div>
                    <div v-if="todayStats.items.length === 0" style="color:#999;">暫無數據</div>
                </div>
            </div>
        </div>

        <div class="orders-grid">
            <div v-for="order in activeOrders" :key="order.id" class="order-card" :style="{ opacity: order.is_done ? '0.5' : '1' }">
                <div class="header">
                    <span class="order-no">單號 #{{ order.id }}</span>
                    <span class="time">{{ new Date(order.created_at).toLocaleTimeString() }}</span>
                </div>
                
                <div class="items">
                    <div v-for="item in order.items" :key="item.id" class="item-row">
                        <span>
                            {{ item.product_name }}
                            <span style="font-size: 0.85em; color: #888; margin-left: 5px;">
                                ${{ item.price || 0 }}
                            </span>
                        </span>
                        <span class="qty">x{{ item.quantity }}</span>
                    </div>
                </div>

                <div class="actions">
                    <button v-if="order.is_done" class="is_done-btn" disabled>✅ 已完成</button>
                    <button v-else class="done-btn" @click="completeOrder(order)">完成餐點</button>
                </div>
            </div>
        </div>
    </div>
</template>

