<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\UnitRequest;
use App\Models\Unit;

class UnitRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return Unit::class;
    }

    /**
     * Store unit by request.
     *
     * @param  UnitRequest  $request  The unit request
     */
    public static function storeByRequest(UnitRequest $request): Unit
    {
        $shop = generaleSetting('rootShop');

        return self::create([
            'name' => $request->name,
            'shop_id' => $shop->id,
            'is_active' => true,
        ]);
    }

    /**
     * Update unit by request.
     *
     * @param  UnitRequest  $request  The unit request
     * @param  Unit  $unit  The unit to update
     * @return Unit The updated unit
     */
    public static function updateByRequest(UnitRequest $request, Unit $unit): Unit
    {
        $unit->update([
            'name' => $request->name,
        ]);

        return $unit;
    }
}
