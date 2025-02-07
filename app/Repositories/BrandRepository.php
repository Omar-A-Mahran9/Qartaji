<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\BrandRequest;
use App\Models\Brand;

class BrandRepository extends Repository
{
    /**
     * Get the model
     * model() brand
     */
    public static function model()
    {
        return Brand::class;
    }

    /**
     * store a new brand
     */
    public static function storeByRequest(BrandRequest $request): Brand
    {
        $shop = generaleSetting('rootShop');

        return self::create([
            'name' => $request->name,
            'is_active' => true,
            'shop_id' => $shop->id,
        ]);
    }

    /**
     * update a brand
     */
    public static function updateByRequest(BrandRequest $request, Brand $brand): Brand
    {
        $brand->update([
            'name' => $request->name,
        ]);

        return $brand;
    }
}
