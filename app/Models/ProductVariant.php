<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductVariant extends Model
{
    /** @use HasFactory<\Database\Factories\ProductVariantFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'variant_name',
        'price',
        'compare_price',
        'stock_count',
        'stock_status',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_price' => 'decimal:2',
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the product that owns this variant.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the variant values for this variant.
     */
    public function variantValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariantValue::class, 'product_variant_combinations', 'product_variant_id', 'variant_value_id');
    }

    /**
     * Check if variant is in stock.
     */
    public function isInStock(): bool
    {
        return $this->stock_count > 0 && $this->is_active;
    }

    /**
     * Get the effective price (variant price or parent product price).
     */
    public function getEffectivePrice(): float
    {
        return $this->price ?? $this->product->price;
    }

    /**
     * Update stock status based on stock count.
     */
    public function updateStockStatus(): void
    {
        if ($this->stock_count <= 0) {
            $this->stock_status = 'out_of_stock';
        } elseif ($this->stock_count <= 5) {
            $this->stock_status = 'low_stock';
        } else {
            $this->stock_status = 'in_stock';
        }

        $this->save();
    }
}
