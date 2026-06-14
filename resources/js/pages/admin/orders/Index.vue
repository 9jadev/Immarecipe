<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ReceiptText, Search, Filter, Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface OrderRow {
    id: number;
    order_number: string;
    first_name: string;
    last_name: string;
    email: string;
    total: number | string;
    status: string;
    payment_status: string;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
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
    orders: PaginatedOrders;
    filters: {
        search?: string;
        status?: string;
        payment_status?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? '');
const paymentStatusFilter = ref(props.filters.payment_status ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (statusFilter.value) params.set('status', statusFilter.value);
    if (paymentStatusFilter.value) params.set('payment_status', paymentStatusFilter.value);
    if (startDate.value) params.set('start_date', startDate.value);
    if (endDate.value) params.set('end_date', endDate.value);
    const query = params.toString();
    return query ? `/admin/orders/export?${query}` : '/admin/orders/export';
});

watch([search, statusFilter, paymentStatusFilter, startDate, endDate], () => {
    router.get(
        '/admin/orders',
        {
            search: search.value || undefined,
            status: statusFilter.value || undefined,
            payment_status: paymentStatusFilter.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, { deep: true });

const formatMoney = (amount: number | string | null) => {
    const value = amount == null ? 0 : Number(amount);
    return '₦' + (Number.isFinite(value) ? value : 0).toFixed(2);
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

const customerName = (order: OrderRow) => {
    if (order.user?.name) return order.user.name;
    return `${order.first_name} ${order.last_name}`.trim();
};

const customerEmail = (order: OrderRow) => {
    return order.user?.email ?? order.email;
};
</script>

<template>

    <Head title="Orders" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <ReceiptText class="h-8 w-8" />
                    Orders
                </h1>
                <p class="text-muted-foreground">View and manage customer orders.</p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <a :href="exportUrl">Export to Excel</a>
                </Button>
                <Button as-child>
                    <Link href="/admin/orders/create">
                        <Plus class="h-4 w-4 mr-2" />
                        New Order
                    </Link>
                </Button>
            </div>
        </div>

        <Card>
            <CardHeader class="pb-4">
                <CardTitle class="text-base flex items-center gap-2">
                    <Filter class="h-4 w-4" />
                    Filters
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-5">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search order #, name, email..." class="pl-9" />
                    </div>
                    <Select v-model="statusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="processing">Processing</SelectItem>
                            <SelectItem value="shipped">Shipped</SelectItem>
                            <SelectItem value="delivered">Delivered</SelectItem>
                            <SelectItem value="cancelled">Cancelled</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="paymentStatusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Payment Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Payment Status</SelectItem>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="processing">Processing</SelectItem>
                            <SelectItem value="paid">Paid</SelectItem>
                            <SelectItem value="failed">Failed</SelectItem>
                        </SelectContent>
                    </Select>
                    <Input v-model="startDate" type="date" />
                    <Input v-model="endDate" type="date" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>All Orders</CardTitle>
                <CardDescription>
                    A total of {{ orders.total }} orders.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="orders.data.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No orders found.</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Order #</th>
                                <th class="text-left py-3 px-2 font-medium">Customer</th>
                                <th class="text-right py-3 px-2 font-medium">Total</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-left py-3 px-2 font-medium">Payment</th>
                                <th class="text-left py-3 px-2 font-medium">Created</th>
                                <th class="text-right py-3 px-2 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="order in orders.data" :key="order.id" class="border-b border-border/50">
                                <td class="py-3 px-2 font-medium">{{ order.order_number }}</td>
                                <td class="py-3 px-2">
                                    <div>
                                        <p class="font-medium">{{ customerName(order) }}</p>
                                        <p class="text-xs text-muted-foreground">{{ customerEmail(order) }}</p>
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-right tabular-nums font-medium">{{ formatMoney(order.total) }}
                                </td>
                                <td class="py-3 px-2">{{ order.status }}</td>
                                <td class="py-3 px-2">{{ order.payment_status }}</td>
                                <td class="py-3 px-2">{{ formatDateTime(order.created_at) }}</td>
                                <td class="py-3 px-2 text-right">
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="`/admin/orders/${order.id}`">View</Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="orders.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ orders.from }} to {{ orders.to }} of {{ orders.total }} orders
                        </p>
                        <div class="flex gap-1">
                            <Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'"
                                :only="['orders']">
                                <Button variant="outline" size="sm" :disabled="!link.url"
                                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                                    v-html="link.label.replace('&laquo;', '←').replace('&raquo;', '→')" />
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
