<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponCollect extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];

    /**
     * get the coupon
     * */
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    /**
     * get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
