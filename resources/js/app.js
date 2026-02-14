import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue'; // 我們等一下要建這個檔案
import axios from 'axios';
import router from './router';



window.axios = axios;
window.axios.defaults.baseURL = 'http://127.0.0.1:8000';

const app = createApp(App);
app.use(router);
app.use(createPinia());
app.mount('#app');