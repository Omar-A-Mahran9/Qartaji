<?php

namespace App\Models;

<<<<<<< HEAD
=======
use App\Traits\HasTranslations;
>>>>>>> 6e9408d1d7ed1293240c4a9370795c0ca8120470
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerifyOtp extends Model
{
<<<<<<< HEAD
    use HasFactory;
=======
    use HasFactory,HasTranslations;
>>>>>>> 6e9408d1d7ed1293240c4a9370795c0ca8120470

    protected $guarded = ['id'];
}
