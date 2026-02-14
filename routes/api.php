<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CategoryController;




Route::apiResource('orders', OrderController::class);
Route::apiResource('products', ProductController::class);
Route::apiResource('categories', CategoryController::class);



// 定義網址：當有人訪問 /menu 時，執行 MenuController 的 index 方法
Route::get('/menu', [MenuController::class, 'index']);
Route::patch('/order-items/{id}/toggle', [OrderController::class, 'toggleItemStatus']);
Route::get('/orders/active', [OrderController::class, 'index']);

