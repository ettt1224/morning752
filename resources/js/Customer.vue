<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import axios from 'axios';
import { useCartStore } from './stores/cart';

// --- 資料與狀態 ---
const menu = ref([]);
const cart = useCartStore();
const currentCategoryId = ref(null);

// --- 彈跳視窗控制 (Modal) ---
const showOptionModal = ref(false);
const selectedProduct = ref(null); 

// --- 載入菜單 ---
onMounted(async () => {
    try {
        const response = await axios.get('/api/menu');
        
        // 🛠️ 修改點 1：幫每個商品初始化 tempQty (預設數量 1)
        menu.value = response.data.map(category => ({
            ...category,
            products: category.products.map(product => ({
                ...product,
                tempQty: 1 // 預設 1 份
            }))
        }));
        
        if (menu.value.length > 0) {
            currentCategoryId.value = menu.value[0].id;
        }
    } catch (error) {
        console.error('菜單載入失敗:', error);
    }
});

const currentProducts = computed(() => {
    const activeCategory = menu.value.find(c => c.id === currentCategoryId.value);
    return activeCategory ? activeCategory.products : [];
});

const changeCategory = (id) => {
    currentCategoryId.value = id;
}

// 🛠️ 修改點 2：新增數量的加減函式
const increaseQty = (product) => {
    product.tempQty++;
};

const decreaseQty = (product) => {
    if (product.tempQty > 1) {
        product.tempQty--;
    }
};

// --- 🛒 加入購物車邏輯 ---
const handleAddToCart = (product) => {
    if (Boolean(product.has_cold_hot)) {
        selectedProduct.value = product;
        showOptionModal.value = true;
    } else {
        // 🛠️ 修改點 3：傳入數量 (請確保你的 Store addToCart 支援第二個參數，或會自動累加)
        // 這裡我們假設你的 store 是簡單的 push，所以我們傳多次或傳物件
        // 最通用的寫法是呼叫 store，並傳入當前設定的數量
        
        // 如果你的 store 寫法是 addToCart(product)，通常會預設加 1
        // 為了相容，我們可以在這裡跑迴圈，或是去改 store (建議後者)
        // 這裡示範最保險的做法：修改傳入的物件，讓 store 知道數量
        
        const productToAdd = { ...product, qty: product.tempQty };
        cart.addToCart(productToAdd); // 注意：你的 store 需要能處理傳入的 qty

        // 加入後重置為 1
        product.tempQty = 1;
    }
};

// --- 確認選擇冷/熱 ---
const confirmOption = (type) => { 
    if (!selectedProduct.value) return;

    const variantProduct = {
        ...selectedProduct.value,
        id: `${selectedProduct.value.id}-${type === '冰' ? 'cold' : 'hot'}`,
        name: `${selectedProduct.value.name} (${type})`,
        price: selectedProduct.value.price,
        // 🛠️ 修改點 4：把選擇的數量帶入變體商品
        qty: selectedProduct.value.tempQty 
    };

    cart.addToCart(variantProduct);
    
    // 重置數量與視窗
    selectedProduct.value.tempQty = 1; // 重置數量
    showOptionModal.value = false;
    selectedProduct.value = null;
};

// --- 送出訂單 ---
const submitOrder = async () => {
    if (cart.items.length === 0) return alert('購物車是空的！');

    try {
        await axios.post('/api/orders', {
            items: cart.items,
            total: cart.totalPrice,
            type: 'dine_in'
        });
        alert('訂單送出成功！');
        cart.clearCart();
    } catch (error) {
        alert('訂單送出失敗，請檢查後端日誌');
        console.error(error);
    }
};
</script>

<template>
    <div class="app-container">
        
        <div class="menu-section">
            <header class="main-header">
                <h1>🍔點餐</h1>
            </header>

            <div class="category-nav">
                <button 
                    v-for="category in menu" 
                    :key="category.id"
                    class="cat-btn"
                    :class="{ 'active': currentCategoryId === category.id }"
                    @click="changeCategory(category.id)"
                >
                    {{ category.name }}
                </button>
            </div>

            <div class="products-grid">
                <div v-for="product in currentProducts" :key="product.id" class="product-card">
                    
                    <div class="card-body">
                        <div class="title-row">
                            <h3>{{ product.name }}</h3>
                            <span v-if="product.has_cold_hot" ></span>
                        </div>
                        
                        <p class="desc" v-if="product.description">{{ product.description }}</p>
                        
                        <div class="card-footer">
                            <span class="price">${{ product.price }}</span>
                            
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <button 
                                    @click.stop="decreaseQty(product)" 
                                    style="width: 25px; height: 25px; border-radius: 50%; border: 1px solid #ddd; background: white; cursor: pointer; color: #666; display: flex; align-items: center; justify-content: center;"
                                >
                                    -
                                </button>
                                
                                <span style="font-weight: bold; min-width: 20px; text-align: center;">
                                    {{ product.tempQty || 1 }}
                                </span>

                                <button 
                                    @click.stop="increaseQty(product)" 
                                    style="width: 25px; height: 25px; border-radius: 50%; border: 1px solid #ddd; background: white; cursor: pointer; color: #666; display: flex; align-items: center; justify-content: center;"
                                >
                                    +
                                </button>
                                <button class="add-btn" @click="handleAddToCart(product)" style="margin-left: 5px;">
                                    加入
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="currentProducts.length === 0" class="no-products">
                    此分類暫無商品
                </div>
            </div>
        </div>

        <div class="cart-section">
            <div class="cart-header">
                <h2>🛒 購物車</h2>
                <span class="badge" v-if="cart.items.length > 0">{{ cart.items.length }}</span>
            </div>

            <div class="cart-body">
                <div v-if="cart.items.length === 0" class="empty-cart">
                    <p>尚未選購商品</p>
                </div>
                <ul v-else class="cart-list">
                    <li v-for="item in cart.items" :key="item.id" class="cart-item">
                        <div class="item-info">
                            <div class="item-name">{{ item.name }}</div>
                            <div class="item-price">${{ item.price }} x {{ item.qty }}</div>
                        </div>
                        <div class="item-actions">
                            <span class="item-total">${{ item.price * item.qty }}</span>
                            <button @click="cart.removeFromCart(item.id)" class="del-btn">✕</button>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="cart-footer">
                <div class="total-row">
                    <span>總計</span>
                    <span class="total-price">${{ cart.totalPrice }}</span>
                </div>
                <button 
                    @click="submitOrder" 
                    class="checkout-btn" 
                    :disabled="cart.items.length === 0"
                    :class="{ 'disabled': cart.items.length === 0 }"
                >
                    送出訂單
                </button>
            </div>
        </div>

        <div v-if="showOptionModal" class="modal-overlay" @click.self="showOptionModal = false">
            <div class="modal-card bounce-in">
                <div class="modal-header">
                    <h3>{{ selectedProduct?.name }}</h3>
                    <p>請選擇溫度 (數量: {{ selectedProduct?.tempQty }})</p>
                </div>
                <div class="modal-body">
                    <button class="opt-btn cold" @click="confirmOption('冰')">
                         冰 
                    </button>
                    <button class="opt-btn hot" @click="confirmOption('熱')">
                         熱
                    </button>
                </div>
                <button class="cancel-modal" @click="showOptionModal = false">取消</button>
            </div>
        </div>

    </div>
</template>

