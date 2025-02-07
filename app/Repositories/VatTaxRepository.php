<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Enums\VatTaxType;
use App\Http\Requests\VatTaxRequest;
use App\Models\VatTax;

class VatTaxRepository extends Repository
{
    public static function model()
    {
        return VatTax::class;
    }

    /**
     * Get the order base tax.
     *
     * @return \App\Models\VatTax
     */
    public static function getOrderBaseTax()
    {
        return self::query()->where('type', VatTaxType::ORDERBASE->value)->first();
    }

    public static function getActiveVatTaxes()
    {
        return self::query()->where('is_active', true)->where('type', '!=', VatTaxType::ORDERBASE->value)->get();
    }

    public static function storeByRequest(VatTaxRequest $request)
    {
        return self::create([
            'name' => $request->name,
            'percentage' => $request->percentage,
            'type' => VatTaxType::PRODUCTBASE->value,
        ]);
    }

    public static function updateByRequest(VatTax $vatTax, VatTaxRequest $request)
    {
        return $vatTax->update([
            'name' => $request->name,
            'percentage' => $request->percentage,
        ]);
    }

    public static function toggle(VatTax $vatTax)
    {
        return $vatTax->update([
            'is_active' => ! $vatTax->is_active,
        ]);
    }

    /**
     * Update the order base vat tax.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\VatTax
     */
    public static function updateOrderBaseTax($request)
    {
        $orderBaseTax = self::getOrderBaseTax();

        return self::query()->updateOrCreate([
            'id' => $orderBaseTax?->id ?? null,
        ], [
            'percentage' => $request->percentage,
            'deduction' => $request->deduction,
            'type' => VatTaxType::ORDERBASE->value,
        ]);
    }
}
