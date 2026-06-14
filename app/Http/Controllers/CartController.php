<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\DispatchLocation;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    /**
     * Display the cart page.
     */
    public function index(Request $request)
    {
        $cart = Cart::getCurrentCart();
        $cart->load('items.product.category', 'items.variant');

        // Check for pending order from flash session (after order creation)
        $pendingOrder = $request->session()->get('order');
        $dispatchLocations = DispatchLocation::active()->ordered()->get(['id', 'name', 'price']);

        return Inertia::render('Storefront/Cart', [
            'cart' => [
                'items' => $cart->items->map(fn($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                    'product' => [
                        'id' => $item->product->id,
                        'name' => $item->product->name,
                        'slug' => $item->product->slug,
                        'price' => $item->product->price,
                        'images' => $item->product->images,
                        'is_active' => $item->product->is_active,
                        'stock_count' => $item->product->stock_count,
                        'category' => $item->product->category?->only(['id', 'name', 'slug']),
                    ],
                    'variant' => $item->variant ? [
                        'id' => $item->variant->id,
                        'sku' => $item->variant->sku,
                        'variant_name' => $item->variant->variant_name,
                        'price' => $item->variant->price,
                        'stock_count' => $item->variant->stock_count,
                        'images' => $item->variant->images,
                    ] : null,
                ]),
                'total_items' => $cart->total_items,
                'subtotal' => $cart->subtotal,
            ],
            'dispatchLocations' => $dispatchLocations,
            'order' => $pendingOrder,
        ]);
    }

    /**
     * Add item to cart.
     */
    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'variant_name' => 'nullable|string',
            'quantity' => 'integer|min:1|max:10',
        ]);

        $product = Product::active()->findOrFail($request->product_id);
        $variant = null;
        $variantName = null;

        // Check if product has variants
        if ($product->hasVariants()) {
            if (!$request->product_variant_id) {
                return back()->with('error', 'Please select a product variant.');
            }
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('id', $request->product_variant_id)
                ->firstOrFail();
            $variantName = $request->variant_name ?? $variant->variant_name;

            if (!$variant->isInStock()) {
                return back()->with('error', 'This variant is out of stock.');
            }
        } else {
            if (!$product->isInStock()) {
                return back()->with('error', 'This product is out of stock.');
            }
        }

        $cart = Cart::getCurrentCart();
        $cart->addItem($product, $request->quantity ?? 1, $variant?->id, $variantName);

        return back()->with('success', 'Product added to cart!');
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:0|max:10',
        ]);

        $cart = Cart::getCurrentCart();
        $cart->updateItem($request->product_id, $request->quantity, $request->product_variant_id);

        return back()->with('success', 'Cart updated!');
    }

    /**
     * Remove item from cart.
     */
    public function remove(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
        ]);

        $cart = Cart::getCurrentCart();
        $cart->removeItem($request->product_id, $request->product_variant_id);

        return back()->with('success', 'Item removed from cart.');
    }

    /**
     * Clear the cart.
     */
    public function clear()
    {
        $cart = Cart::getCurrentCart();
        $cart->clear();

        return back()->with('success', 'Cart cleared.');
    }

    /**
     * Get cart count for header.
     */
    public function count()
    {
        $cart = Cart::getCurrentCart();

        return response()->json([
            'count' => $cart->total_items,
        ]);
    }
}
