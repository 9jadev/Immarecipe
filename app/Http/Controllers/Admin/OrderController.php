<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DispatchLocation;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class OrderController extends Controller
{
    private const ORDER_STATUSES = [
        'pending',
        'processing',
        'shipped',
        'delivered',
        'cancelled',
    ];

    private const PAYMENT_STATUSES = [
        'pending',
        'processing',
        'paid',
        'failed',
    ];

    private function buildOrdersQuery(Request $request)
    {
        $ordersQuery = Order::query()
            ->with(['user:id,name,email'])
            ->select([
                'id',
                'user_id',
                'order_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'subtotal',
                'delivery_fee',
                'total',
                'status',
                'payment_status',
                'paid_at',
                'created_at',
            ]);

        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $ordersQuery->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $ordersQuery->whereDate('created_at', '<=', $endDate);
        }

        $ordersQuery
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($q) use ($search) {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereRaw("concat(first_name, ' ', last_name) like ?", ["%{$search}%"]);
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')->toString()))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->string('payment_status')->toString()));

        return $ordersQuery;
    }

    public function index(Request $request)
    {
        $orders = $this->buildOrdersQuery($request)->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['search', 'status', 'payment_status', 'start_date', 'end_date']),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildOrdersQuery($request)
            ->latest()
            ->get([
                'order_number',
                'first_name',
                'last_name',
                'email',
                'phone',
                'subtotal',
                'delivery_fee',
                'total',
                'status',
                'payment_status',
                'paid_at',
                'created_at',
            ]);

        $filename = 'orders_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Order #',
                'Customer',
                'Email',
                'Phone',
                'Subtotal',
                'Delivery Fee',
                'Total',
                'Status',
                'Payment Status',
                'Paid At',
                'Created At',
            ]);

            foreach ($rows as $order) {
                $customer = trim(($order->first_name ?? '') . ' ' . ($order->last_name ?? ''));

                fputcsv($handle, [
                    $order->order_number,
                    $customer,
                    $order->email,
                    $order->phone,
                    (string) $order->subtotal,
                    (string) $order->delivery_fee,
                    (string) $order->total,
                    (string) $order->status,
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

    public function create()
    {
        $dispatchLocations = DispatchLocation::active()
            ->ordered()
            ->get(['id', 'name', 'price']);

        $products = Product::active()
            ->select(['id', 'name', 'price', 'stock_count', 'images'])
            ->with(['variants' => function ($query) {
                $query->where('is_active', true)
                    ->select(['id', 'product_id', 'variant_name', 'price', 'stock_count', 'images', 'is_active']);
            }])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/orders/Create', [
            'dispatchLocations' => $dispatchLocations,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'country' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'dispatch_location_id' => [
                'required',
                'integer',
                Rule::exists('dispatch_locations', 'id')->where('is_active', true),
            ],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_status' => ['required', 'in:pending,processing,paid,failed'],
            'paid_at' => ['nullable', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'items.*.product_variant_id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')],
            'items.*.variant_name' => ['nullable', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        $order = DB::transaction(function () use ($validated) {
            $dispatchLocation = DispatchLocation::active()->whereKey($validated['dispatch_location_id'])->first();
            if (!$dispatchLocation) {
                throw ValidationException::withMessages([
                    'dispatch_location_id' => 'Please select a valid dispatch location.',
                ]);
            }

            $userId = User::where('email', $validated['email'])->value('id');

            $lineItems = [];
            $subtotal = 0.0;

            foreach ($validated['items'] as $index => $item) {
                $product = Product::active()->whereKey($item['product_id'])->first();
                if (!$product) {
                    throw ValidationException::withMessages([
                        "items.{$index}.product_id" => 'Please select a valid product.',
                    ]);
                }

                $variantId = $item['product_variant_id'] ?? null;
                $variant = null;
                $variantName = $item['variant_name'] ?? null;

                if ($variantId) {
                    $variant = ProductVariant::where('product_id', $product->id)
                        ->whereKey($variantId)
                        ->where('is_active', true)
                        ->first();

                    if (!$variant) {
                        throw ValidationException::withMessages([
                            "items.{$index}.product_variant_id" => 'Please select a valid variant.',
                        ]);
                    }

                    $variantName = $variantName ?? $variant->variant_name;

                    if ($variant->stock_count < $item['quantity']) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => 'Not enough stock for the selected variant.',
                        ]);
                    }
                } else {
                    if ($product->stock_count < $item['quantity']) {
                        throw ValidationException::withMessages([
                            "items.{$index}.quantity" => 'Not enough stock for the selected product.',
                        ]);
                    }
                }

                $price = (float) ($variant?->price ?? $product->price);
                $total = $price * (int) $item['quantity'];
                $subtotal += $total;

                $lineItems[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'variant_name' => $variantName,
                    'quantity' => (int) $item['quantity'],
                    'price' => $price,
                    'total' => $total,
                ];
            }

            $deliveryFee = (float) $dispatchLocation->price;
            $paidAt = ($validated['payment_status'] === 'paid')
                ? ($validated['paid_at'] ?? now())
                : null;

            $order = Order::create([
                'user_id' => $userId,
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
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => $subtotal + $deliveryFee,
                'notes' => $validated['notes'] ?? null,
                'status' => $validated['status'],
                'payment_method' => $validated['payment_method'] ?? null,
                'payment_reference' => $validated['payment_reference'] ?? null,
                'payment_status' => $validated['payment_status'],
                'paid_at' => $paidAt,
            ]);

            foreach ($lineItems as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product_id'],
                    'product_variant_id' => $line['product_variant_id'],
                    'variant_name' => $line['variant_name'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'total' => $line['total'],
                ]);

                if ($line['variant']) {
                    $line['variant']->decrement('stock_count', $line['quantity']);
                    $line['variant']->refresh();
                    $line['variant']->updateStockStatus();
                } else {
                    $line['product']->decrement('stock_count', $line['quantity']);
                    $line['product']->refresh();
                    $line['product']->updateStockStatus();
                }
            }

            return $order;
        });

        return redirect()->route('admin.orders.show', $order);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(self::ORDER_STATUSES)],
        ]);

        $order->update([
            'status' => $validated['status'],
        ]);

        return back();
    }

    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => ['required', Rule::in(self::PAYMENT_STATUSES)],
        ]);

        $paymentStatus = $validated['payment_status'];

        $order->update([
            'payment_status' => $paymentStatus,
            'paid_at' => $paymentStatus === 'paid' ? ($order->paid_at ?? now()) : null,
        ]);

        return back();
    }

    public function show(Order $order)
    {
        $order->load([
            'user:id,name,email',
            'items.product:id,name,slug,sku',
        ]);

        return Inertia::render('admin/orders/Show', [
            'order' => $order,
        ]);
    }
}
