<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOffer extends Model
{
    protected $guarded = [];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    protected $appends = ['background_image_url'];

    public function items(): HasMany
    {
        return $this->hasMany(ProductOfferItem::class, 'product_offer_id');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('product_offer_items.status', 1);
    }

    public function getBackgroundImageUrlAttribute(): string
    {
        return get_file_url() . '/' . $this->background_image;
    }
}
