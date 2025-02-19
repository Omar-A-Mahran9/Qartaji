<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderProduct extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];

    public function shopOrder(): HasOne
    {
        return $this->hasOne(ShopOrder::class, 'shop_order_id');
    }
}
