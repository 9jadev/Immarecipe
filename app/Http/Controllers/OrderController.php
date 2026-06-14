<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\DispatchLocation;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    /**
     * Store a new order (pending payment).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:500',
            'country' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'city' => 'nullable|string|max:100',
            'dispatch_location_id' => [
                'required',
                'integer',
                Rule::exists('dispatch_locations', 'id')->where('is_active', true),
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        $cart = Cart::getCurrentCart();
        $cart->load('items.product');

        if ($cart->items->isEmpty()) {
            return back()->with('error', 'Your cart is empty.');
        }

        // Check stock availability
        foreach ($cart->items as $item) {
            if (!$item->product->is_active || $item->product->stock_count < $item->quantity) {
                return back()->with('error', "Product '{$item->product->name}' is no longer available in the requested quantity.");
            }
        }

        $order = DB::transaction(function () use ($validated, $cart, $request) {
            $dispatchLocation = DispatchLocation::active()->whereKey($validated['dispatch_location_id'])->first();
            if (!$dispatchLocation) {
                throw ValidationException::withMessages([
                    'dispatch_location_id' => 'Please select a valid dispatch location.',
                ]);
            }

            $deliveryFee = (float) $dispatchLocation->price;

            // Create order with pending payment status
            $order = Order::create([
                'user_id' => $request->user()?->id,
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'address' => $validated['address'],
                'country' => $validated['country'],
                'state' => $validated['state'],
                'city' => $validated['city'],
                'dispatch_location_id' => $dispatchLocation->id,
                'dispatch_location_name' => $dispatchLocation->name,
                'subtotal' => $cart->subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $cart->subtotal + $deliveryFee,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'payment_status' => 'pending',
            ]);

            // Create order items and update stock
            foreach ($cart->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'total' => $item->total,
                ]);

                // Decrease stock
                $item->product->decrement('stock_count', $item->quantity);
            }

            // Clear the cart
            $cart->clear();

            return $order;
        });

        // Store order email in session for guest users (for payment verification)
        if (!$request->user()) {
            $request->session()->put('pending_order_email', $order->email);
        }

        // Return order data for payment modal
        return redirect()->route('cart')->with([
            'order' => $order->load('items.product'),
        ]);
    }

    /**
     * Show order details.
     */
    public function show(Request $request, Order $order)
    {
        // Ensure user can view this order
        if ($request->user() && $order->user_id !== $request->user()->id) {
            abort(403, 'Unauthorized access to this order.');
        }

        $order->load('items.product');

        return Inertia::render('Storefront/OrderDetails', [
            'order' => $order,
        ]);
    }

    /**
     * Show order success page.
     */
    public function success()
    {
        return Inertia::render('Storefront/OrderSuccess');
    }
}
