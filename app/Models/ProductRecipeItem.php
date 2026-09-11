<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductRecipeItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_optional' => 'boolean',
        'meta' => 'array',
    ];

    public function recipe()
    {
        return $this->belongsTo(ProductRecipe::class, 'product_recipe_id');
    }

    public function ingredient()
    {
        return $this->belongsTo(Product::class, 'ingredient_product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariantCombination::class, 'variant_id');
    }
}

