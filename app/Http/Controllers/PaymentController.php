<?php

namespace App\Http\Controllers;

use App\Mail\PurchaseConfirmationMail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PaymentController extends Controller
{
    /**
     * Initialize payment with the selected gateway.
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'payment_method' => 'required|in:flutterwave,safe_haven',
        ]);

        $order = Order::findOrFail($request->order_id);

        // Ensure order belongs to the user or session
        if ($order->user_id !== $request->user()?->id && $order->email !== $request->session()->get('pending_order_email')) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Check if order is already paid
        if ($order->payment_status === 'paid') {
            return response()->json(['error' => 'Order already paid'], 400);
        }

        // Update order with payment method
        $order->update([
            'payment_method' => $request->payment_method,
            'payment_status' => 'processing',
        ]);

        try {
            if ($request->payment_method === 'flutterwave') {
                return $this->initializeFlutterwave($order);
            } elseif ($request->payment_method === 'safe_haven') {
                return $this->initializeSafeHaven($order);
            }
        } catch (\Exception $e) {
            Log::error('Payment initialization failed', [
                'order_id' => $order->id,
                'payment_method' => $request->payment_method,
                'error' => $e->getMessage(),
            ]);

            $order->update(['payment_status' => 'failed']);

            return response()->json(['error' => 'Payment initialization failed'], 500);
        }
    }

    /**
     * Initialize Flutterwave payment.
     */
    private function initializeFlutterwave(Order $order)
    {
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->post('https://api.flutterwave.com/v3/payments', [
                'tx_ref' => $order->order_number,
                'amount' => $order->total,
                'currency' => 'NGN',
                'redirect_url' => route('payment.callback', ['gateway' => 'flutterwave']),
                'customer' => [
                    'email' => $order->email,
                    'name' => $order->full_name,
                    'phonenumber' => $order->phone,
                ],
                'customizations' => [
                    'title' => 'Immah Store',
                    'description' => "Payment for Order {$order->order_number}",
                    'logo' => asset('favicon.svg'),
                ],
                'meta' => [
                    'order_id' => $order->id,
                ],
            ]);

        if ($response->successful() && $response->json('status') === 'success') {
            return response()->json([
                'payment_url' => $response->json('data.link'),
                'reference' => $order->order_number,
            ]);
        }

        throw new \Exception($response->json('message', 'Flutterwave initialization failed'));
    }

    /**
     * Initialize Safe Haven payment.
     */
    private function initializeSafeHaven(Order $order)
    {
        // Get Safe Haven access token
        $accessToken = $this->getSafeHavenAccessToken();

        $response = Http::withToken($accessToken)
            ->post(config('services.safe_haven.base_url') . '/payments', [
                'orderId' => $order->order_number,
                'amount' => (int) ($order->total * 100), // Convert to kobo
                'currency' => 'NGN',
                'redirectUrl' => route('payment.callback', ['gateway' => 'safe_haven']),
                'customer' => [
                    'email' => $order->email,
                    'name' => $order->full_name,
                    'phoneNumber' => $order->phone,
                ],
                'metadata' => [
                    'order_id' => $order->id,
                ],
            ]);

        if ($response->successful()) {
            $data = $response->json('data');

            return response()->json([
                'payment_url' => $data['paymentUrl'] ?? $data['checkoutUrl'],
                'reference' => $order->order_number,
            ]);
        }

        throw new \Exception($response->json('message', 'Safe Haven initialization failed'));
    }

    /**
     * Get Safe Haven access token.
     */
    private function getSafeHavenAccessToken(): string
    {
        $response = Http::post(config('services.safe_haven.base_url') . '/auth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.safe_haven.client_id'),
            'client_secret' => config('services.safe_haven.client_secret'),
        ]);

        if ($response->successful()) {
            return $response->json('access_token');
        }

        throw new \Exception('Failed to get Safe Haven access token');
    }

    /**
     * Handle payment callback from gateways.
     */
    public function callback(Request $request, string $gateway)
    {
        Log::info('Payment callback received', [
            'gateway' => $gateway,
            'request' => $request->all(),
        ]);

        if ($gateway === 'flutterwave') {
            return $this->handleFlutterwaveCallback($request);
        } elseif ($gateway === 'safe_haven') {
            return $this->handleSafeHavenCallback($request);
        }

        return redirect()->route('cart')->with('error', 'Invalid payment gateway');
    }

    /**
     * Handle Flutterwave callback.
     */
    private function handleFlutterwaveCallback(Request $request)
    {
        $transactionId = $request->get('transaction_id');
        $txRef = $request->get('tx_ref');

        // Verify transaction
        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        if ($response->successful() && $response->json('status') === 'success') {
            $data = $response->json('data');

            if ($data['tx_ref'] === $txRef && $data['status'] === 'successful') {
                $order = Order::where('order_number', $txRef)->first();

                if ($order) {
                    if ($order->payment_status !== 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'payment_reference' => $transactionId,
                            'paid_at' => now(),
                            'status' => 'processing',
                            'payment_metadata' => $data,
                        ]);

                        $this->sendPurchaseEmail($order);
                    }

                    return redirect()->route('order.success')
                        ->with('success', 'Payment successful! Your order is being processed.');
                }
            }
        }

        return redirect()->route('cart')->with('error', 'Payment verification failed');
    }

    /**
     * Handle Safe Haven callback.
     */
    private function handleSafeHavenCallback(Request $request)
    {
        $orderId = $request->get('orderId');
        $paymentReference = $request->get('paymentReference');

        // Verify payment
        $accessToken = $this->getSafeHavenAccessToken();
        $response = Http::withToken($accessToken)
            ->get(config('services.safe_haven.base_url') . "/payments/{$paymentReference}");

        if ($response->successful()) {
            $data = $response->json('data');

            if ($data['status'] === 'successful' || $data['status'] === 'completed') {
                $order = Order::where('order_number', $orderId)->first();

                if ($order) {
                    if ($order->payment_status !== 'paid') {
                        $order->update([
                            'payment_status' => 'paid',
                            'payment_reference' => $paymentReference,
                            'paid_at' => now(),
                            'status' => 'processing',
                            'payment_metadata' => $data,
                        ]);

                        $this->sendPurchaseEmail($order);
                    }

                    return redirect()->route('order.success')
                        ->with('success', 'Payment successful! Your order is being processed.');
                }
            }
        }

        return redirect()->route('cart')->with('error', 'Payment verification failed');
    }

    /**
     * Handle Flutterwave webhook.
     */
    public function flutterwaveWebhook(Request $request)
    {
        $signature = $request->header('verif-hash');

        if ($signature !== config('services.flutterwave.webhook_secret')) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $data = $request->json('data');

        if ($request->json('event') === 'charge.completed' && $data['status'] === 'successful') {
            $order = Order::where('order_number', $data['tx_ref'])->first();

            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $data['id'],
                    'paid_at' => now(),
                    'status' => 'processing',
                    'payment_metadata' => $data,
                ]);

                $this->sendPurchaseEmail($order);
            }
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Handle Safe Haven webhook.
     */
    public function safeHavenWebhook(Request $request)
    {
        Log::info('Safe Haven webhook received', $request->all());

        $data = $request->json('data');

        if (isset($data['orderId']) && in_array($data['status'], ['successful', 'completed'])) {
            $order = Order::where('order_number', $data['orderId'])->first();

            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'paid',
                    'payment_reference' => $data['paymentReference'] ?? null,
                    'paid_at' => now(),
                    'status' => 'processing',
                    'payment_metadata' => $data,
                ]);

                $this->sendPurchaseEmail($order);
            }
        }

        return response()->json(['status' => 'success']);
    }

    private function sendPurchaseEmail(Order $order): void
    {
        try {
            $order->loadMissing(['items.product']);

            $appName = config('app.name', 'Immarecipe');
            $customerName = $order->full_name;

            $city = $order->city ? ", {$order->city}" : '';
            $dispatchLocation = $order->dispatch_location_name ? " ({$order->dispatch_location_name})" : '';
            $shippingAddress = "{$order->address}{$city}, {$order->state}, {$order->country}{$dispatchLocation}";

            $itemsHtml = '';
            foreach ($order->items as $item) {
                $productName = $item->product?->name ?? 'Item';
                $variant = $item->variant_name ? ' - ' . $item->variant_name : '';
                $lineTotal = '₦' . number_format((float) $item->total, 2);
                $itemsHtml .= '<li style="margin: 0 0 6px 0;">'
                    . e($productName . $variant)
                    . ' × '
                    . e((string) $item->quantity)
                    . ' <span style="float:right;">'
                    . e($lineTotal)
                    . '</span></li>';
            }

            $subtotal = '₦' . number_format((float) $order->subtotal, 2);
            $delivery = '₦' . number_format((float) $order->delivery_fee, 2);
            $total = '₦' . number_format((float) $order->total, 2);

            $subject = "{$appName} - Order Confirmation ({$order->order_number})";

            $html = '<div style="font-family: ui-sans-serif, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial;">'
                . '<h2 style="margin:0 0 8px 0;">Thanks for your purchase, ' . e($customerName) . '.</h2>'
                . '<p style="margin:0 0 16px 0;">We received your payment and your order is now being processed.</p>'
                . '<p style="margin:0 0 16px 0;"><strong>Order:</strong> ' . e($order->order_number) . '</p>'
                . '<div style="margin:0 0 16px 0;">'
                . '<strong>Items</strong>'
                . '<ul style="padding-left:18px; margin:8px 0 0 0; list-style:disc;">' . $itemsHtml . '</ul>'
                . '</div>'
                . '<div style="border-top:1px solid #e5e7eb; padding-top:12px; margin-top:12px;">'
                . '<p style="margin:0 0 6px 0;"><span>Subtotal</span><span style="float:right;">' . e($subtotal) . '</span></p>'
                . '<p style="margin:0 0 6px 0;"><span>Delivery</span><span style="float:right;">' . e($delivery) . '</span></p>'
                . '<p style="margin:10px 0 0 0; font-size:16px;"><strong>Total</strong><span style="float:right;"><strong>' . e($total) . '</strong></span></p>'
                . '<div style="clear:both;"></div>'
                . '</div>'
                . '<div style="margin-top:16px;">'
                . '<strong>Shipping Address</strong>'
                . '<p style="margin:6px 0 0 0;">' . e($shippingAddress) . '</p>'
                . '</div>'
                . '<p style="margin-top:18px; color:#6b7280; font-size:12px;">If you have any issues, reply to this email.</p>'
                . '</div>';

            Mail::to($order->email, $customerName ?: null)->send(
                new PurchaseConfirmationMail($order, $subject, $html),
            );
        } catch (\Throwable $e) {
            Log::error('Failed to send purchase email', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
