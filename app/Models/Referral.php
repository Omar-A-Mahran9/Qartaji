<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory,HasTranslations;

    protected $fillable = [
        'referrer_id',
        'referral_code',
        'referred_user_id',
        'rewarded'
    ];

    // Get the user who referred others
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    // Get the user who was referred
    public function referredUser()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }
}
