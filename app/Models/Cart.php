<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

class Cart extends Model
{
    protected $fillable = [
        'user_id',
        'session_id',
    ];

    /**
     * Get the items in the cart.
     */
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    /**
     * Get the user that owns the cart.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get or create cart for current user/session.
     */
    public static function getCurrentCart(): self
    {
        $sessionId = Session::getId();

        if (auth()->check()) {
            $cart = static::where('user_id', auth()->id())
                ->with('items.product.category')
                ->first();
        } else {
            $cart = static::where('session_id', $sessionId)
                ->whereNull('user_id')
                ->with('items.product.category')
                ->first();
        }

        if (!$cart) {
            $cart = static::create([
                'user_id' => auth()->id(),
                'session_id' => auth()->check() ? null : $sessionId,
            ]);
        }

        return $cart;
    }

    /**
     * Add item to cart.
     */
    public function addItem(Product $product, int $quantity = 1, ?int $variantId = null, ?string $variantName = null): CartItem
    {
        // Check for existing item with same product AND variant
        $query = $this->items()->where('product_id', $product->id);
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }
        $item = $query->first();

        // Get price from variant or product
        $price = $product->price;
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            if ($variant && $variant->price !== null) {
                $price = $variant->price;
            }
        }

        if ($item) {
            $item->increment('quantity', $quantity);
            // Ensure variant_name is set if it wasn't before
            if ($variantName && !$item->variant_name) {
                $item->update(['variant_name' => $variantName]);
            }
            return $item;
        }

        return $this->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'variant_name' => $variantName,
            'quantity' => $quantity,
            'price' => $price,
        ]);
    }

    /**
     * Update item quantity.
     */
    public function updateItem(int $productId, int $quantity, ?int $variantId = null): ?CartItem
    {
        $query = $this->items()->where('product_id', $productId);
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }
        $item = $query->first();

        if ($item) {
            if ($quantity <= 0) {
                $item->delete();
                return null;
            }
            $item->update(['quantity' => $quantity]);
            return $item;
        }

        return null;
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(int $productId, ?int $variantId = null): bool
    {
        $query = $this->items()->where('product_id', $productId);
        if ($variantId) {
            $query->where('product_variant_id', $variantId);
        } else {
            $query->whereNull('product_variant_id');
        }
        return $query->delete() > 0;
    }

    /**
     * Clear all items from cart.
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * Get total items count.
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->items->sum('quantity');
    }

    /**
     * Get subtotal.
     */
    public function getSubtotalAttribute(): float
    {
        return $this->items->sum(fn($item) => $item->price * $item->quantity);
    }

    /**
     * Merge guest cart into user cart on login.
     */
    public static function mergeGuestCart(int $userId): void
    {
        $sessionId = Session::getId();

        $guestCart = static::where('session_id', $sessionId)
            ->whereNull('user_id')
            ->first();

        if ($guestCart) {
            $userCart = static::firstOrCreate(
                ['user_id' => $userId],
                ['user_id' => $userId]
            );

            foreach ($guestCart->items as $item) {
                $existingItem = $userCart->items()
                    ->where('product_id', $item->product_id)
                    ->where('product_variant_id', $item->product_variant_id)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', $item->quantity);
                } else {
                    $userCart->items()->create([
                        'product_id' => $item->product_id,
                        'product_variant_id' => $item->product_variant_id,
                        'variant_name' => $item->variant_name,
                        'quantity' => $item->quantity,
                        'price' => $item->price,
                    ]);
                }
            }

            $guestCart->delete();
        }
    }
}
