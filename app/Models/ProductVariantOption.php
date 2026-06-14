<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariantOption extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantOptionFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
    ];

    /**
     * Get the product that owns this variant option.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the values for this variant option.
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductVariantValue::class, 'variant_option_id')->orderBy('position');
    }
}
