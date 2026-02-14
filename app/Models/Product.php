<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    // 商品屬於一個分類
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
