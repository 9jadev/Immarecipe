<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Receipt, User as UserIcon } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface UserSummary {
    id: number;
    name: string;
    email: string;
    created_at: string;
    orders_count: number;
    paid_orders_count: number;
    total_amount_spent: number | string | null;
}

interface OrderRow {
    id: number;
    order_number: string;
    total: number | string;
    status: string;
    payment_status: string;
    paid_at: string | null;
    created_at: string;
}

interface PaginatedOrders {
    data: OrderRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    user: UserSummary;
    orders: PaginatedOrders;
}>();

defineOptions({
    layout: AdminLayout,
});

const formatMoney = (amount: number | string | null) => {
    const value = amount == null ? 0 : Number(amount);
    return '₦' + (Number.isFinite(value) ? value : 0).toFixed(2);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const formatDateTime = (date: string) => {
    return new Date(date).toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>

<template>
    <Head :title="`User: ${user.name}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <UserIcon class="h-6 w-6" />
                    <h1 class="text-3xl font-bold tracking-tight">{{ user.name }}</h1>
                </div>
                <p class="text-muted-foreground">{{ user.email }}</p>
            </div>

            <Button variant="outline" as-child>
                <Link href="/admin/users">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Orders</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-semibold tabular-nums">
                    {{ user.orders_count }}
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Paid Orders</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-semibold tabular-nums">
                    {{ user.paid_orders_count }}
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Total Spent</CardTitle>
                </CardHeader>
                <CardContent class="text-2xl font-semibold tabular-nums">
                    {{ formatMoney(user.total_amount_spent) }}
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <Receipt class="h-5 w-5" />
                    Orders
                </CardTitle>
                <CardDescription>
                    Joined {{ formatDate(user.created_at) }} · Showing {{ orders.total }} orders
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="orders.data.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No orders found for this user.</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Order #</th>
                                <th class="text-right py-3 px-2 font-medium">Total</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-left py-3 px-2 font-medium">Payment</th>
                                <th class="text-left py-3 px-2 font-medium">Paid At</th>
                                <th class="text-left py-3 px-2 font-medium">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders.data" :key="order.id" class="border-b border-border/50">
                                <td class="py-3 px-2 font-medium">{{ order.order_number }}</td>
                                <td class="py-3 px-2 text-right tabular-nums font-medium">{{ formatMoney(order.total) }}</td>
                                <td class="py-3 px-2">{{ order.status }}</td>
                                <td class="py-3 px-2">{{ order.payment_status }}</td>
                                <td class="py-3 px-2">
                                    <span v-if="order.paid_at">{{ formatDateTime(order.paid_at) }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="py-3 px-2">{{ formatDateTime(order.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="orders.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                        </p>
                        <div class="flex gap-1">
                            <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" :only="['orders']">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="!link.url"
                                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                                    v-html="link.label.replace('&laquo;', '←').replace('&raquo;', '→')"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>

