<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PaymentController extends Controller
{
    private function buildPaymentsQuery(Request $request)
    {
        $query = Order::query()
            ->with('user')
            ->whereNotNull('payment_method');

        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('payment_reference', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->string('payment_status')->toString());
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->string('payment_method')->toString());
        }

        return $query;
    }

    public function index(Request $request)
    {
        $payments = $this->buildPaymentsQuery($request)->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/payments/Index', [
            'payments' => $payments,
            'filters' => $request->only(['search', 'payment_status', 'payment_method', 'start_date', 'end_date']),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildPaymentsQuery($request)
            ->latest()
            ->get([
                'id',
                'order_number',
                'first_name',
                'last_name',
                'email',
                'total',
                'payment_method',
                'payment_reference',
                'payment_status',
                'paid_at',
                'created_at',
            ]);

        $filename = 'payments_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order #',
                'Customer',
                'Email',
                'Amount',
                'Method',
                'Reference',
                'Status',
                'Paid At',
                'Created At',
            ]);

            foreach ($rows as $order) {
                $customer = trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''));

                fputcsv($handle, [
                    $order->order_number,
                    $customer,
                    $order->email,
                    (string) $order->total,
                    (string) ($order->payment_method ?? ''),
                    (string) ($order->payment_reference ?? ''),
                    (string) $order->payment_status,
                    $order->paid_at?->toDateTimeString(),
                    $order->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(Order $order)
    {
        $order->load('user', 'items.product');

        return Inertia::render('admin/payments/Show', [
            'payment' => $order,
        ]);
    }

    public function requery(Order $order)
    {
        if (!$order->payment_method) {
            return back()->with('error', 'No payment method found for this order.');
        }

        try {
            if ($order->payment_method === 'flutterwave') {
                $this->requeryFlutterwave($order);
            } elseif ($order->payment_method === 'safe_haven') {
                $this->requerySafeHaven($order);
            } else {
                return back()->with('error', 'Unsupported payment method.');
            }
        } catch (ValidationException $e) {
            $message = collect($e->errors())->flatten()->first() ?? 'Payment requery failed.';
            return back()->with('error', $message);
        } catch (\Throwable $e) {
            Log::error('Payment requery failed', [
                'order_id' => $order->id,
                'payment_method' => $order->payment_method,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Payment requery failed.');
        }

        return back()->with('success', 'Payment status refreshed.');
    }

    private function requeryFlutterwave(Order $order): void
    {
        $transactionId = $order->payment_reference;
        if (!$transactionId) {
            throw ValidationException::withMessages([
                'payment_reference' => 'No Flutterwave transaction reference stored for this payment.',
            ]);
        }

        $response = Http::withToken(config('services.flutterwave.secret_key'))
            ->get("https://api.flutterwave.com/v3/transactions/{$transactionId}/verify");

        if (!$response->successful() || $response->json('status') !== 'success') {
            throw ValidationException::withMessages([
                'payment_reference' => 'Could not verify Flutterwave transaction.',
            ]);
        }

        $data = $response->json('data');
        $txRef = $data['tx_ref'] ?? null;
        $status = $data['status'] ?? null;

        if ($txRef && $txRef !== $order->order_number) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Transaction does not match this order.',
            ]);
        }

        $updates = [
            'payment_metadata' => $data,
        ];

        if ($status === 'successful') {
            $updates['payment_status'] = 'paid';
            $updates['paid_at'] = $order->paid_at ?? now();
            $updates['status'] = $order->status === 'pending' ? 'processing' : $order->status;
        } elseif (\in_array($status, ['failed', 'cancelled'], true)) {
            $updates['payment_status'] = 'failed';
        } else {
            $updates['payment_status'] = 'processing';
        }

        $order->update($updates);
    }

    private function requerySafeHaven(Order $order): void
    {
        $paymentReference = $order->payment_reference;
        if (!$paymentReference) {
            throw ValidationException::withMessages([
                'payment_reference' => 'No Safe Haven payment reference stored for this payment.',
            ]);
        }

        $accessToken = $this->getSafeHavenAccessToken();

        $response = Http::withToken($accessToken)
            ->get(config('services.safe_haven.base_url') . "/payments/{$paymentReference}");

        if (!$response->successful()) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Could not verify Safe Haven payment.',
            ]);
        }

        $data = $response->json('data');
        $orderId = $data['orderId'] ?? null;
        $status = $data['status'] ?? null;

        if ($orderId && $orderId !== $order->order_number) {
            throw ValidationException::withMessages([
                'payment_reference' => 'Payment does not match this order.',
            ]);
        }

        $updates = [
            'payment_metadata' => $data,
        ];

        if (\in_array($status, ['successful', 'completed'], true)) {
            $updates['payment_status'] = 'paid';
            $updates['paid_at'] = $order->paid_at ?? now();
            $updates['status'] = $order->status === 'pending' ? 'processing' : $order->status;
        } elseif (\in_array($status, ['failed', 'cancelled'], true)) {
            $updates['payment_status'] = 'failed';
        } else {
            $updates['payment_status'] = 'processing';
        }

        $order->update($updates);
    }

    private function getSafeHavenAccessToken(): string
    {
        $response = Http::post(config('services.safe_haven.base_url') . '/auth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => config('services.safe_haven.client_id'),
            'client_secret' => config('services.safe_haven.client_secret'),
        ]);

        if ($response->successful() && $response->json('access_token')) {
            return (string) $response->json('access_token');
        }

        throw ValidationException::withMessages([
            'payment_reference' => 'Safe Haven authentication failed.',
        ]);
    }
}
