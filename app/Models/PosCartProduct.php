<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosCartProduct extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function posCart()
    {
        return $this->belongsTo(PosCart::class);
    }
}
