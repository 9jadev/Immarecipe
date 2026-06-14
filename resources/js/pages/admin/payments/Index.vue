<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CreditCard, Search, Filter } from 'lucide-vue-next';
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

interface PaymentRow {
    id: number;
    order_number: string;
    first_name: string;
    last_name: string;
    email: string;
    total: number | string;
    payment_method: string | null;
    payment_reference: string | null;
    payment_status: string;
    paid_at: string | null;
    created_at: string;
    user: { id: number; name: string; email: string } | null;
}

interface PaginatedPayments {
    data: PaymentRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    payments: PaginatedPayments;
    filters: {
        search?: string;
        payment_method?: string;
        payment_status?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search ?? '');
const paymentMethodFilter = ref(props.filters.payment_method ?? '');
const paymentStatusFilter = ref(props.filters.payment_status ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (paymentMethodFilter.value) params.set('payment_method', paymentMethodFilter.value);
    if (paymentStatusFilter.value) params.set('payment_status', paymentStatusFilter.value);
    if (startDate.value) params.set('start_date', startDate.value);
    if (endDate.value) params.set('end_date', endDate.value);
    const query = params.toString();
    return query ? `/admin/payments/export?${query}` : '/admin/payments/export';
});

watch([search, paymentMethodFilter, paymentStatusFilter, startDate, endDate], () => {
    router.get(
        '/admin/payments',
        {
            search: search.value || undefined,
            payment_method: paymentMethodFilter.value || undefined,
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

const customerName = (row: PaymentRow) => {
    if (row.user?.name) return row.user.name;
    return `${row.first_name} ${row.last_name}`.trim();
};

const customerEmail = (row: PaymentRow) => {
    return row.user?.email ?? row.email;
};
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <CreditCard class="h-8 w-8" />
                    Payments
                </h1>
                <p class="text-muted-foreground">View payment status, references, and requery results.</p>
            </div>
            <Button variant="outline" as-child>
                <a :href="exportUrl">Export to Excel</a>
            </Button>
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
                        <Input v-model="search" placeholder="Search order #, email, reference..." class="pl-9" />
                    </div>
                    <Select v-model="paymentMethodFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Methods" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Methods</SelectItem>
                            <SelectItem value="flutterwave">Flutterwave</SelectItem>
                            <SelectItem value="safe_haven">Safe Haven</SelectItem>
                            <SelectItem value="cash">Cash</SelectItem>
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
                <CardTitle>All Payments</CardTitle>
                <CardDescription>
                    A total of {{ payments.total }} payments.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="payments.data.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No payments found.</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Order #</th>
                                <th class="text-left py-3 px-2 font-medium">Customer</th>
                                <th class="text-right py-3 px-2 font-medium">Amount</th>
                                <th class="text-left py-3 px-2 font-medium">Method</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-left py-3 px-2 font-medium">Paid at</th>
                                <th class="text-right py-3 px-2 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in payments.data" :key="row.id" class="border-b border-border/50">
                                <td class="py-3 px-2 font-medium">{{ row.order_number }}</td>
                                <td class="py-3 px-2">
                                    <div>
                                        <p class="font-medium">{{ customerName(row) }}</p>
                                        <p class="text-xs text-muted-foreground">{{ customerEmail(row) }}</p>
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-right tabular-nums font-medium">{{ formatMoney(row.total) }}
                                </td>
                                <td class="py-3 px-2">{{ row.payment_method ?? '—' }}</td>
                                <td class="py-3 px-2">{{ row.payment_status }}</td>
                                <td class="py-3 px-2">
                                    <span v-if="row.paid_at">{{ formatDateTime(row.paid_at) }}</span>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="`/admin/payments/${row.id}`">View</Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="payments.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ payments.from }} to {{ payments.to }} of {{ payments.total }} payments
                        </p>
                        <div class="flex gap-1">
                            <Link v-for="link in payments.links" :key="link.label" :href="link.url || '#'"
                                :only="['payments']">
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
