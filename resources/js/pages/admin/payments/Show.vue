<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CreditCard, RefreshCw, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface OrderItemRow {
    id: number;
    quantity: number;
    price: number | string;
    total: number | string;
    product: { id: number; name: string; slug: string; sku: string } | null;
}

interface PaymentRow {
    id: number;
    order_number: string;
    payment_method: string | null;
    payment_reference: string | null;
    payment_status: string;
    paid_at: string | null;
    payment_metadata: unknown;
    user: { id: number; name: string; email: string } | null;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    total: number | string;
    created_at: string;
    items?: OrderItemRow[];
}

const props = defineProps<{
    payment: PaymentRow;
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

const customerName = computed(() => {
    if (props.payment.user?.name) return props.payment.user.name;
    return `${props.payment.first_name} ${props.payment.last_name}`.trim();
});

const customerEmail = computed(() => props.payment.user?.email ?? props.payment.email);

const metadataText = computed(() => {
    if (!props.payment.payment_metadata) return null;
    try {
        return JSON.stringify(props.payment.payment_metadata, null, 2);
    } catch {
        return String(props.payment.payment_metadata);
    }
});

const canRequery = computed(() => {
    return props.payment.payment_method === 'flutterwave' || props.payment.payment_method === 'safe_haven';
});

const requeryForm = useForm({});

const requery = () => {
    requeryForm.post(`/admin/payments/${props.payment.id}/requery`, {
        preserveScroll: true,
        onSuccess: (page) => {
            const flash = page.props.flash as { success?: string; error?: string } | undefined;
            if (flash?.success) toast.success(flash.success);
            else if (flash?.error) toast.error(flash.error);
            else toast.success('Payment status refreshed.');
        },
        onError: () => toast.error('Payment requery failed.'),
    });
};
</script>

<template>
    <Head :title="`Payment: ${payment.order_number}`" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <CreditCard class="h-6 w-6" />
                    <h1 class="text-3xl font-bold tracking-tight">{{ payment.order_number }}</h1>
                </div>
                <p class="text-muted-foreground">Created {{ formatDateTime(payment.created_at) }}</p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/payments">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="`/admin/orders/${payment.id}`">
                        <ExternalLink class="h-4 w-4 mr-2" />
                        View Order
                    </Link>
                </Button>
                <Button :disabled="requeryForm.processing || !canRequery" @click="requery">
                    <RefreshCw class="h-4 w-4 mr-2" />
                    {{ requeryForm.processing ? 'Requerying…' : 'Requery Payment' }}
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-3">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Amount</CardTitle>
                </CardHeader>
                <CardContent class="text-xl font-semibold tabular-nums">
                    {{ formatMoney(payment.total) }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Status</CardTitle>
                </CardHeader>
                <CardContent class="text-xl font-semibold">
                    {{ payment.payment_status }}
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm text-muted-foreground">Paid at</CardTitle>
                </CardHeader>
                <CardContent class="text-sm">
                    <span v-if="payment.paid_at">{{ formatDateTime(payment.paid_at) }}</span>
                    <span v-else class="text-muted-foreground">—</span>
                </CardContent>
            </Card>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Customer</CardTitle>
                    <CardDescription>Customer details used for the payment/order.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div>
                        <div class="text-muted-foreground">Name</div>
                        <div class="font-medium">{{ customerName }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Email</div>
                        <div class="font-medium">{{ customerEmail }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Phone</div>
                        <div class="font-medium">{{ payment.phone }}</div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Gateway</CardTitle>
                    <CardDescription>Payment gateway info and reference.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div>
                        <div class="text-muted-foreground">Method</div>
                        <div class="font-medium">{{ payment.payment_method ?? '—' }}</div>
                    </div>
                    <div>
                        <div class="text-muted-foreground">Reference</div>
                        <div class="font-medium break-all">{{ payment.payment_reference ?? '—' }}</div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card v-if="metadataText">
            <CardHeader>
                <CardTitle>Payment Metadata</CardTitle>
                <CardDescription>Latest response saved from the gateway.</CardDescription>
            </CardHeader>
            <CardContent>
                <pre class="text-xs bg-muted/40 border border-border rounded-lg p-4 overflow-auto">{{ metadataText }}</pre>
            </CardContent>
        </Card>
    </div>
</template>
