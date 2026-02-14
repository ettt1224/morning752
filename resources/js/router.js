// resources/js/router.js
import { createRouter, createWebHistory } from 'vue-router';

// 引入你的兩個頁面
import Customer from './Customer.vue';
import Kitchen from './Kitchen.vue';
import Admin from './Admin.vue'; // 引入新頁面
const routes = [
    {
        path: '/', 
        name: 'customer',
        component: Customer // 首頁顯示點餐
    },
    {
        path: '/kitchen', 
        name: 'kitchen',
        component: Kitchen // /kitchen 顯示廚房
    },

    {
        path: '/admin',
        name: 'admin',
        component: Admin
    }


];

const router = createRouter({
    history: createWebHistory(),
    routes,

    
});



export default router;