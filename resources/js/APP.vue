<script setup>

import router from './router';


</script>

<template>
    <div class="main-layout">
        <nav class="navbar" v-if="router.currentRoute.value.path != '/'">
            <router-link to="/">點餐</router-link>|
            <router-link to="/kitchen">廚房</router-link>|
            <router-link to="/admin">餐點</router-link>
        </nav>
        <router-view></router-view>
    </div>
</template>

<style scoped>
.navbar {
    padding: 15px;
    background: #eee;
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.2em;
}
a {
    text-decoration: none;
    color: #333;
    font-weight: bold;
    margin: 0 10px;
}
a.router-link-active {
    color: #d35400; /* 當前頁面變色 */
}
</style>