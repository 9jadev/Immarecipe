<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariantValue extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantValueFactory> */
    use HasFactory;

    protected $fillable = [
        'variant_option_id',
        'value',
        'position',
    ];

    /**
     * Get the variant option that owns this value.
     */
    public function variantOption(): BelongsTo
    {
        return $this->belongsTo(ProductVariantOption::class, 'variant_option_id');
    }

    /**
     * Get the product variants that use this value.
     */
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_combinations', 'variant_value_id', 'product_variant_id');
    }
}
