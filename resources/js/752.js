import { ref, reactive, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

// --- 全域設定 ---
const API_BASE = 'http://127.0.0.1:8000'; // 後端主網址

// ==========================================
// 1. 廚房監控邏輯 (Kitchen)
// ==========================================
export function useKitchen() {
    const LOCAL_STORAGE_KEY = 'kitchen_completed_orders';
    const activeOrders = ref([]);
    const showStats = ref(false);
    let timer = null;

    // 抓取訂單並與本地「完成紀錄」對比
    const fetchOrders = async () => {
        try {
            const res = await axios.get(`${API_BASE}/api/orders`);
            const completedIds = JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY) || '[]');
            activeOrders.value = res.data.map(order => ({
                ...order,
                is_done: completedIds.includes(order.id)
            }));
        } catch (e) { console.error('廚房抓取失敗:', e); }
    };

    // 完成餐點 (僅存於本地)
    const completeOrder = (order) => {
        order.is_done = true;
        const completedIds = JSON.parse(localStorage.getItem(LOCAL_STORAGE_KEY) || '[]');
        if (!completedIds.includes(order.id)) {
            completedIds.push(order.id);
            localStorage.setItem(LOCAL_STORAGE_KEY, JSON.stringify(completedIds));
        }
    };

    // 換日清除完成紀錄
    const clearFinished = () => {
        if (!confirm('確定要清除已完成單據嗎？')) return;
        localStorage.removeItem(LOCAL_STORAGE_KEY);
        activeOrders.value = activeOrders.value.filter(o => !o.is_done);
    };

    // 今日營收與熱銷統計
    const todayStats = computed(() => {
        const todayStr = new Date().toDateString();
        const todayOrders = activeOrders.value.filter(o => new Date(o.created_at).toDateString() === todayStr);
        let revenue = 0;
        const counts = {};
        todayOrders.forEach(o => {
            o.items?.forEach(i => {
                counts[i.product_name] = (counts[i.product_name] || 0) + (Number(i.quantity) || 0);
                revenue += (Number(i.price) || 0) * (Number(i.quantity) || 0);
            });
        });
        return { count: todayOrders.length, revenue, items: Object.entries(counts).sort((a,b)=>b[1]-a[1]) };
    });

    // 匯出報表
    const exportCSV = () => {
        let csv = "\uFEFF單號,時間,商品,數量,單價,總計,狀態\n";
        activeOrders.value.forEach(o => {
            o.items.forEach(i => {
                csv += `${o.id},${new Date(o.created_at).toLocaleTimeString()},${i.product_name},${i.quantity},${i.price},${i.price*i.quantity},${o.is_done?'完成':'製作'}\n`;
            });
        });
        const link = document.createElement("a");
        link.href = URL.createObjectURL(new Blob([csv], { type: "text/csv;charset=utf-8;" }));
        link.download = `廚房報表_${new Date().toISOString().slice(0,10)}.csv`;
        link.click();
    };

    const playSound = () => { new Audio('/ding.mp3').play().catch(() => {}); };

    onMounted(() => {
        fetchOrders();
        timer = setInterval(fetchOrders, 10000); // 10秒自動刷新
        if (window.Echo) {
            window.Echo.channel('kitchen').listen('NewOrderEvent', (e) => { fetchOrders(); playSound(); });
        }
    });
    onUnmounted(() => { if (timer) clearInterval(timer); });

    return { activeOrders, showStats, todayStats, completeOrder, clearFinished, exportCSV, fetchOrders };
}

// ==========================================
// 2. 點餐系統邏輯 (Ordering)
// ==========================================
export function useOrdering(cartStore) {
    const menu = ref([]);
    const currentCategoryId = ref(null);
    const showOptionModal = ref(false);
    const selectedProduct = ref(null);

    const fetchMenu = async () => {
        try {
            const res = await axios.get(`${API_BASE}/api/menu`);
            menu.value = res.data.map(cat => ({
                ...cat,
                products: cat.products.map(p => ({ ...p, tempQty: 1 }))
            }));
            if (menu.value.length > 0) currentCategoryId.value = menu.value[0].id;
        } catch (e) { console.error('菜單抓取失敗:', e); }
    };

    const currentProducts = computed(() => menu.value.find(c => c.id === currentCategoryId.value)?.products || []);
    
    const increaseQty = (p) => p.tempQty++;
    const decreaseQty = (p) => { if (p.tempQty > 1) p.tempQty--; };

    const handleAddToCart = (product) => {
        if (Boolean(product.has_cold_hot)) {
            selectedProduct.value = product;
            showOptionModal.value = true;
        } else {
            cartStore.addToCart({ ...product, qty: product.tempQty });
            product.tempQty = 1;
        }
    };

    const confirmOption = (type) => {
        cartStore.addToCart({
            ...selectedProduct.value,
            id: `${selectedProduct.value.id}-${type === '冰' ? 'cold' : 'hot'}`,
            name: `${selectedProduct.value.name} (${type})`,
            qty: selectedProduct.value.tempQty
        });
        selectedProduct.value.tempQty = 1;
        showOptionModal.value = false;
    };

    const submitOrder = async () => {
        if (cartStore.items.length === 0) return alert('空購物車');
        try {
            await axios.post(`${API_BASE}/api/orders`, { items: cartStore.items, total: cartStore.totalPrice, type: 'dine_in' });
            alert('訂單送出成功'); cartStore.clearCart();
        } catch (e) { alert('送出失敗'); }
    };

    onMounted(fetchMenu);

    return { menu, currentCategoryId, showOptionModal, selectedProduct, currentProducts, increaseQty, decreaseQty, handleAddToCart, confirmOption, submitOrder };
}

// ==========================================
// 3. 後台管理邏輯 (Admin)
// ==========================================
export function useAdmin() {
    const products = ref([]);
    const categories = ref([]);
    const viewMode = ref("list");
    const isEditing = ref(false);
    const form = reactive({ id: null, name: "", price: "", category_name: "", description: "", image: null, previewUrl: null, has_cold_hot: false });

    const fetchData = async () => {
        try {
            const [p, c] = await Promise.all([axios.get("/api/products"), axios.get("/api/menu")]);
            products.value = p.data;
            categories.value = c.data;
        } catch (e) { console.error('後台抓取失敗:', e); }
    };

    const openEditor = (p = null) => {
        if (p) {
            isEditing.value = true;
            Object.assign(form, { ...p, has_cold_hot: Boolean(p.has_cold_hot), category_name: categories.value.find(c => c.id === p.category_id)?.name || "" });
            form.previewUrl = p.image;
        } else {
            isEditing.value = false;
            Object.assign(form, { id: null, name: "", price: "", category_name: "", description: "", image: null, previewUrl: null, has_cold_hot: false });
        }
        viewMode.value = "form";
        window.scrollTo({ top: 0, behavior: "smooth" });
    };

    const handleFileChange = (e) => {
        const file = e.target.files[0];
        if (file) { form.image = file; form.previewUrl = URL.createObjectURL(file); }
    };

    const submitForm = async () => {
        const fd = new FormData();
        Object.keys(form).forEach(k => { 
            if (form[k] !== null) fd.append(k, k === 'has_cold_hot' ? (form[k] ? 1 : 0) : form[k]); 
        });
        if (isEditing.value) fd.append("_method", "PUT");
        try {
            await axios.post(isEditing.value ? `/api/products/${form.id}` : "/api/products", fd);
            alert('儲存成功'); fetchData(); viewMode.value = "list";
        } catch (e) { alert('儲存失敗'); }
    };

    const deleteProduct = async (id) => {
        if (!confirm("確定刪除商品？")) return;
        await axios.delete(`/api/products/${id}`);
        fetchData();
    };

    const deleteCategory = async (cat) => {
        if (!confirm(`刪除分類「${cat.name}」？`)) return;
        await axios.delete(`/api/categories/${cat.id}`);
        fetchData();
    };

    onMounted(fetchData);

    return { products, categories, viewMode, form, isEditing, openEditor, handleFileChange, submitForm, deleteProduct, deleteCategory, backToList: () => viewMode.value = "list" };
}