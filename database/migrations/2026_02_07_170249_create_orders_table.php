<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
{
    // --- 建立訂單主表 (orders) ---
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        
        $table->string('order_number', 20)->unique()->comment('訂單編號');
        
        // 【重點講解 2】ENUM 列舉型態
        // 限制這個欄位只能存這幾個字串
        $table->enum('type', ['dine_in', 'takeaway'])->comment('內用或外帶');
        
        $table->enum('source', ['web', 'staff'])->default('web')->comment('來源');
        
        $table->enum('status', ['pending', 'completed', 'canceled'])
              ->default('pending')
              ->comment('訂單狀態');
              
        $table->integer('total_amount')->comment('總金額');
        
        $table->timestamps();
    });

    // --- 建立訂單細項表 (order_items) ---
    Schema::create('order_items', function (Blueprint $table) {
        $table->id();
        
        // 設定外鍵：訂單刪除時，細項一起刪除
        $table->foreignId('order_id')
              ->constrained('orders')
              ->cascadeonDelete();
              

        // 這裡 product_id 我們通常不設強制外鍵 (constrained)
        // 因為就算產品以後被刪除了，歷史訂單紀錄還是要保留，不能因為產品刪了導致訂單壞掉
        $table->unsignedBigInteger('product_id'); 
        
        $table->string('product_name', 100)->comment('購買當下的商品名稱');
        $table->integer('price')->comment('購買當下的單價');
        
        $table->integer('quantity')->default(1)->comment('數量');
        
        $table->string('options')->nullable()->comment('客製化選項');

        
        $table->timestamps();
    });
}

public function down()
{
    // 刪除時順序要反過來，先刪子表 (items) 再刪主表 (orders)
    Schema::dropIfExists('order_items');
    Schema::dropIfExists('orders');
}
};
