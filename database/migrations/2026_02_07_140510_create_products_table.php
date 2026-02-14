<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // 【重點講解 1】外鍵設定
            // foreignId('category_id') -> 建立一個 unsigned big integer 欄位
            // constrained('categories') -> 設定它對應到 categories 表的 id
            // onDelete('cascade') -> 如果分類被刪除，底下的產品也一起刪除
            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeonDelete()
                ->comment('對應分類表的ID');

            $table->string('name', 100)->comment('餐點名稱');

            // nullable() 代表這個欄位可以允許 NULL (不一定要填)
            $table->text('description')->nullable()->comment('餐點描述');

            $table->integer('price')->comment('價格');

            $table->string('image')->nullable()->comment('圖片路徑');

            // boolean 對應到 MySQL 的 TINYINT(1)
            $table->boolean('is_enabled')->default(true)->comment('1=上架, 0=下架');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
