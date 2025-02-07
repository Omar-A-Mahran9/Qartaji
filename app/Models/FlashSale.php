<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class FlashSale extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    /**
     * Get the products for the flash sale.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')->withPivot('price', 'quantity', 'discount', 'sale_quantity');
    }

    /**
     * Get the media record associated with the model.
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * Create a thumbnail for the media, with a default image if none is present.
     */
    public function thumbnail(): Attribute
    {
        $thumbnail = asset('defualt/defualt.jpg');
        if ($this->media && Storage::exists($this->media->src)) {
            $thumbnail = Storage::url($this->media->src);
        }

        return new Attribute(
            get: fn () => $thumbnail
        );
    }
}
