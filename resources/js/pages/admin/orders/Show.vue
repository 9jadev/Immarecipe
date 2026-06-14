<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ReceiptText, User as UserIcon, MapPin, Save, Loader2 } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { computed } from 'vue';
import { toast } from 'sonner';

interface OrderItemRow {
    id: number;
    quantity: number;
    price: number | string;
    total: number | string;
    product: { id: number; name: string; slug: string; sku: string } | null;
}

interface OrderRow {
    id: number;
    order_number: string;
    status: string;
    payment_method: string | null;
    payment_reference: string | null;
    payment_status: string;
    paid_at: string | null;
    payment_metadata: unknown;
    user_id: number | null;
    user: { id: number; name: string; email: string } | null;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    address: string;
    country: string;
    state: string;
    city: string | null;
    dispatch_location_name: string | null;
    subtotal: number | string;
    delivery_fee: number | string;
    total: number | string;
    notes: string | null;
    created_at: string;
    items: OrderItemRow[];
}

const props = defineProps<{
    order: OrderRow;
}>();

defineOptions({
    layout: AdminLayout,
});

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

const customerName = () => {
    if (props.order.user?.name) return props.order.user.name;
    return `${props.order.first_name} ${props.order.last_name}`.trim();
};

const customerEmail = () => {
    return props.order.user?.email ?? props.order.email;
};

const statusForm = useForm({
    status: props.order.status,
});

const statusChanged = computed(() => statusForm.status !== props.order.status);

const updateStatus = () => {
    statusForm.clearErrors();
    statusForm.patch(`/admin/orders/${props.order.id}/status`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Order status updated.'),
        onError: () => toast.error('Could not update status.'),
    });
};

const paymentStatusForm = useForm({
    payment_status: props.order.payment_status,
});

const paymentStatusChanged = computed(() => paymentStatusForm.payment_status !== props.order.payment_status);

const updatePaymentStatus = () => {
    paymentStatusForm.clearErrors();
    paymentStatusForm.patch(`/admin/orders/${props.order.id}/payment-status`, {
        preserveScroll: true,
        onSuccess: () => toast.success('Payment status updated.'),
        onError: () => toast.error('Could not update payment status.'),
    });
};
</script>

<template>

    <Head :title="`Order: ${order.order_number}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <ReceiptText class="h-6 w-6" />
                    <h1 class="text-3xl font-bold tracking-tight">{{ order.order_number }}</h1>
                </div>
                <p class="text-muted-foreground">Created {{ formatDateTime(order.created_at) }}</p>
            </div>

            <Button variant="outline" as-child>
                <Link href="/admin/orders">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Link>
            </Button>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Order Status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="grid gap-2">
                        <Select v-model="statusForm.status">
                            <SelectTrigger :class="{ 'border-destructive': statusForm.errors.status }">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="processing">Processing</SelectItem>
                                <SelectItem value="shipped">Shipped</SelectItem>
                                <SelectItem value="delivered">Delivered</SelectItem>
                                <SelectItem value="cancelled">Cancelled</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="statusForm.errors.status" class="text-sm text-destructive">{{ statusForm.errors.status
                            }}</p>
                    </div>

                    <Button size="sm" :disabled="statusForm.processing || !statusChanged" @click="updateStatus">
                        <Loader2 v-if="statusForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                        <Save v-else class="h-4 w-4 mr-2" />
                        Save
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Payment Status</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div class="grid gap-2">
                        <Select v-model="paymentStatusForm.payment_status">
                            <SelectTrigger :class="{ 'border-destructive': paymentStatusForm.errors.payment_status }">
                                <SelectValue placeholder="Select payment status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="processing">Processing</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                                <SelectItem value="failed">Failed</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="paymentStatusForm.errors.payment_status" class="text-sm text-destructive">{{
                            paymentStatusForm.errors.payment_status }}</p>
                    </div>

                    <Button size="sm" :disabled="paymentStatusForm.processing || !paymentStatusChanged"
                        @click="updatePaymentStatus">
                        <Loader2 v-if="paymentStatusForm.processing" class="h-4 w-4 mr-2 animate-spin" />
                        <Save v-else class="h-4 w-4 mr-2" />
                        Save
                    </Button>
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Total</CardTitle>
                </CardHeader>
                <CardContent class="text-xl font-semibold tabular-nums">
                    {{ formatMoney(order.total) }}
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UserIcon class="h-5 w-5" />
                        Customer
                    </CardTitle>
                    <CardDescription>Customer details for this order.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-1">
                        <p class="font-medium">{{ customerName() }}</p>
                        <p class="text-sm text-muted-foreground">{{ customerEmail() }}</p>
                        <p class="text-sm text-muted-foreground">{{ order.phone }}</p>
                        <div v-if="order.user_id" class="pt-2">
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/admin/users/${order.user_id}`">View user</Link>
                            </Button>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MapPin class="h-5 w-5" />
                        Shipping
                    </CardTitle>
                    <CardDescription>Delivery address and dispatch location.</CardDescription>
                </CardHeader>
                <CardContent>
                    <div class="space-y-1 text-sm">
                        <p class="font-medium">{{ order.address }}</p>
                        <p class="text-muted-foreground">
                            {{ [order.city, order.state, order.country].filter(Boolean).join(', ') }}
                        </p>
                        <p v-if="order.dispatch_location_name" class="text-muted-foreground">
                            Dispatch location: {{ order.dispatch_location_name }}
                        </p>
                        <p v-if="order.notes" class="text-muted-foreground">
                            Notes: {{ order.notes }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Items</CardTitle>
                <CardDescription>A list of items in this order.</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="order.items.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No items found.</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Product</th>
                                <th class="text-right py-3 px-2 font-medium">Qty</th>
                                <th class="text-right py-3 px-2 font-medium">Price</th>
                                <th class="text-right py-3 px-2 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in order.items" :key="item.id" class="border-b border-border/50">
                                <td class="py-3 px-2">
                                    <div v-if="item.product">
                                        <p class="font-medium">{{ item.product.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ item.product.sku }}</p>
                                    </div>
                                    <div v-else class="text-muted-foreground">-</div>
                                </td>
                                <td class="py-3 px-2 text-right tabular-nums">{{ item.quantity }}</td>
                                <td class="py-3 px-2 text-right tabular-nums">{{ formatMoney(item.price) }}</td>
                                <td class="py-3 px-2 text-right tabular-nums font-medium">{{ formatMoney(item.total) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="mt-4 flex justify-end">
                        <div class="w-full max-w-sm space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span class="tabular-nums">{{ formatMoney(order.subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Delivery</span>
                                <span class="tabular-nums">{{ formatMoney(order.delivery_fee) }}</span>
                            </div>
                            <div class="flex items-center justify-between border-t pt-2">
                                <span class="font-medium">Total</span>
                                <span class="font-medium tabular-nums">{{ formatMoney(order.total) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>Payment</CardTitle>
                <CardDescription>Payment information for this order.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="grid gap-3 md:grid-cols-2 text-sm">
                    <div>
                        <p class="text-muted-foreground">Method</p>
                        <p class="font-medium">{{ order.payment_method ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Reference</p>
                        <p class="font-medium">{{ order.payment_reference ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Paid At</p>
                        <p class="font-medium">{{ order.paid_at ? formatDateTime(order.paid_at) : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">Status</p>
                        <p class="font-medium">{{ order.payment_status }}</p>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
