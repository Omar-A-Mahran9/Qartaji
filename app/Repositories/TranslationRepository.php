<?php
namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Models\Translation;

class TranslationRepository extends Repository
{
    public static function model()
    {
        return Translation::class;    
    }
}