<?php

namespace App\Repositories;

use Abedin\Maker\Repositories\Repository;
use App\Http\Requests\SubCategoryRequest;
use App\Models\SubCategory;
use Illuminate\Support\Str;

class SubCategoryRepository extends Repository
{
    /**
     * base method
     *
     * @method model()
     */
    public static function model()
    {
        return SubCategory::class;
    }

    /**
     * store a new category
     */
    public static function storeByRequest(SubCategoryRequest $request): SubCategory
    {
        $shop = generaleSetting('rootShop');

        $thumbnail = MediaRepository::storeByRequest(
            $request->file('thumbnail'),
            'categories',
            'image'
        );

        $subCategory = self::create([
            'shop_id' => $shop->id,
            'name' => $request->name,
            'media_id' => $thumbnail->id ?? null,
            'slug' => Str::slug($request->name, '-'),
            'is_active' => true,
        ]);

        $subCategory->categories()->attach($request->category);

        return $subCategory;
    }

    /**
     * update a category
     */
    public static function updateByRequest(SubCategoryRequest $request, SubCategory $subCategory): SubCategory
    {
        $thumbnail = $subCategory->media;
        if ($request->hasFile('thumbnail')) {
            $thumbnail = MediaRepository::updateByRequest(
                $request->file('thumbnail'),
                'categories',
                'image',
                $thumbnail
            );
        }

        $subCategory->update([
            'name' => $request->name,
            'media_id' => $thumbnail->id ?? $subCategory->media_id,
            'slug' => Str::slug($request->name, '-'),
        ]);

        $subCategory->categories()->sync($request->category);

        return $subCategory;
    }
}
