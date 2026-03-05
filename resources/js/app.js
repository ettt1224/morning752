import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue'; // 我們等一下要建這個檔案
import axios from 'axios';
import router from './router';



window.axios = axios;

// 移除硬編碼的 localhost，讓它在雲端自動對應正確的網址
if (import.meta.env.VITE_API_BASE_URL) {
    window.axios.defaults.baseURL = import.meta.env.VITE_API_BASE_URL;
}


const app = createApp(App);
app.use(router);
app.use(createPinia());
app.mount('#app');