<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ThemeColor extends Model
{
    use HasFactory,HasTranslations;

    protected $guarded = ['id'];
}
