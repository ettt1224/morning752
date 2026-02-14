import { defineStore } from 'pinia';

export const useCartStore = defineStore('cart', {
    state: () => ({
        items: [],
    }),
    getters: {
        totalPrice: (state) => state.items.reduce((total, item) => total + item.price * item.qty, 0),
        totalQty: (state) => state.items.reduce((total, item) => total + item.qty, 0),
    },
    actions: {
        addToCart(product) {
            // 🔥 關鍵修正：定義 qtyToAdd 變數
            // 讀取 App.vue 傳過來的 qty，如果沒有傳就預設為 1
            const qtyToAdd = product.qty || 1; 

            // 檢查購物車內是否已有此商品
            const existingItem = this.items.find(item => item.id === product.id);

            if (existingItem) {
                // 🔥 累加數量 (使用定義好的變數)
                existingItem.qty += qtyToAdd;
            } else {
                // 🔥 新增商品 (帶入數量)
                this.items.push({
                    ...product,
                    qty: qtyToAdd 
                });
            }
        },
        removeFromCart(productId) {
            this.items = this.items.filter(item => item.id !== productId);
        },
        clearCart() {
            this.items = [];
        }
    }
});