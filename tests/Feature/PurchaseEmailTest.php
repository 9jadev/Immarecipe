<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Mail\PurchaseConfirmationMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use function Pest\Laravel\get;

test('purchase email is sent after successful flutterwave callback', function () {
    Mail::fake();

    $product = Product::create([
        'name' => 'Test Product',
        'price' => 1000,
    ]);

    $order = Order::create([
        'order_number' => 'ORD-TEST-EMAIL-1',
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'customer@example.com',
        'phone' => '08000000000',
        'address' => '123 Street',
        'country' => 'Nigeria',
        'state' => 'Abia',
        'city' => 'Umuahia',
        'subtotal' => 1000,
        'delivery_fee' => 500,
        'total' => 1500,
        'status' => 'pending',
        'payment_status' => 'processing',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => null,
        'variant_name' => null,
        'quantity' => 1,
        'price' => 1000,
        'total' => 1000,
    ]);

    Http::fake([
        'https://api.flutterwave.com/v3/transactions/*/verify' => Http::response([
            'status' => 'success',
            'data' => [
                'tx_ref' => $order->order_number,
                'status' => 'successful',
            ],
        ], 200),
    ]);

    $response = get(route('payment.callback', ['gateway' => 'flutterwave']) . '?transaction_id=123&tx_ref=' . $order->order_number);

    $response->assertRedirect(route('order.success', absolute: false));
    expect($order->fresh()->payment_status)->toBe('paid');

    Mail::assertSent(PurchaseConfirmationMail::class);

    $sent = Mail::sent(PurchaseConfirmationMail::class);
    expect($sent)->toHaveCount(1);
    expect($sent->first()->to[0]['address'] ?? null)->toBe($order->email);
});
