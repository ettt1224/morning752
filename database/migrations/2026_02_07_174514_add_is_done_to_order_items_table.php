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
    Schema::table('order_items', function (Blueprint $table) {
        // 加入這個 boolean 欄位，預設為 false (未完成)
        $table->boolean('is_done')->default(false)->after('options');
    });
}

public function down()
{
    Schema::table('order_items', function (Blueprint $table) {
        $table->dropColumn('is_done');
    });
}
};
