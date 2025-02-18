<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];

    /**
     * Scope a query to only include active records.
     */
    public function scopeIsActive($query)
    {
        return $query->where('is_active', true);
    }
}
