<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. 建立分類
        $breakfast = \App\Models\Category::create(['name' => '超人氣早餐', 'sort_order' => 1]);
        $drink = \App\Models\Category::create(['name' => '解渴飲料', 'sort_order' => 2]);

        // 2. 建立商品 (直接透過關聯建立)
        $breakfast->products()->createMany([
            ['name' => '培根蛋餅', 'price' => 45],
            ['name' => '卡拉雞腿堡', 'price' => 75],
        ]);

        $drink->products()->createMany([
            ['name' => '古早味紅茶', 'price' => 25],
            ['name' => '冰豆漿', 'price' => 30],
        ]);
    }
}
