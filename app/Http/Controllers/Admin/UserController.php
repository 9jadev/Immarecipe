<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    private function buildUsersQuery(Request $request)
    {
        $usersQuery = User::query()
            ->select(['id', 'name', 'email', 'created_at'])
            ->withCount('orders')
            ->withCount([
                'orders as paid_orders_count' => fn ($q) => $q->where('payment_status', 'paid'),
            ])
            ->withSum([
                'orders as total_amount_spent' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'total');

        $startDate = $request->string('start_date')->toString();
        $endDate = $request->string('end_date')->toString();
        if ($startDate && $endDate && $startDate > $endDate) {
            [$startDate, $endDate] = [$endDate, $startDate];
        }

        if ($startDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
            $usersQuery->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
            $usersQuery->whereDate('created_at', '<=', $endDate);
        }

        $usersQuery->when($request->filled('search'), function ($query) use ($request) {
            $search = $request->string('search')->toString();

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        });

        return $usersQuery;
    }

    public function index(Request $request)
    {
        $users = $this->buildUsersQuery($request)->latest()->paginate(15)->withQueryString();

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'start_date', 'end_date']),
        ]);
    }

    public function export(Request $request)
    {
        $rows = $this->buildUsersQuery($request)
            ->latest()
            ->get([
                'id',
                'name',
                'email',
                'created_at',
            ]);

        $filename = 'users_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Name',
                'Email',
                'Orders',
                'Paid Orders',
                'Total Spent',
                'Joined',
            ]);

            foreach ($rows as $user) {
                fputcsv($handle, [
                    $user->name,
                    $user->email,
                    (string) ($user->orders_count ?? 0),
                    (string) ($user->paid_orders_count ?? 0),
                    (string) ($user->total_amount_spent ?? 0),
                    $user->created_at?->toDateTimeString(),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function show(User $user)
    {
        $userSummary = User::query()
            ->whereKey($user->id)
            ->select(['id', 'name', 'email', 'created_at'])
            ->withCount('orders')
            ->withCount([
                'orders as paid_orders_count' => fn ($q) => $q->where('payment_status', 'paid'),
            ])
            ->withSum([
                'orders as total_amount_spent' => fn ($q) => $q->where('payment_status', 'paid'),
            ], 'total')
            ->firstOrFail();

        $orders = Order::query()
            ->where('user_id', $user->id)
            ->select([
                'id',
                'order_number',
                'total',
                'status',
                'payment_status',
                'paid_at',
                'created_at',
            ])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/users/Show', [
            'user' => $userSummary,
            'orders' => $orders,
        ]);
    }
}
