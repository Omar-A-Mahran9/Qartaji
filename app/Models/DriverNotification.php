<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverNotification extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id');
    }
}
