<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    // 允許所有欄位被寫入 (方便開發)
    protected $guarded = [];

    // 一個分類有多個商品
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

