<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ReceiptText, Plus, Trash2, Save, AlertCircle, X, Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface DispatchLocation {
    id: number;
    name: string;
    price: number | string;
}

interface ProductVariant {
    id: number;
    variant_name: string;
    price: number | string | null;
    stock_count: number;
    images: string[] | null;
    is_active: boolean;
}

interface Product {
    id: number;
    name: string;
    price: number | string;
    stock_count: number;
    images: string[] | null;
    variants: ProductVariant[];
}

const props = defineProps<{
    dispatchLocations: DispatchLocation[];
    products: Product[];
}>();

defineOptions({
    layout: AdminLayout,
});

const imageUrl = (src: string | null | undefined) => {
    if (!src) return null;

    const trimmed = src.trim();
    if (!trimmed) return null;

    if (
        trimmed.startsWith('http://')
        || trimmed.startsWith('https://')
        || trimmed.startsWith('data:')
        || trimmed.startsWith('blob:')
        || trimmed.startsWith('/')
    ) {
        return trimmed;
    }

    if (trimmed.startsWith('storage/')) {
        return `/${trimmed}`;
    }

    return `/storage/${trimmed}`;
};

const firstImage = (images: string[] | null | undefined) => imageUrl(images?.[0]);
const hideBrokenImage = (event: Event) => {
    const img = event.target as HTMLImageElement | null;
    if (!img) return;
    img.style.display = 'none';
};

const formatMoney = (amount: number | string | null) => {
    const value = amount == null ? 0 : Number(amount);
    return '₦' + (Number.isFinite(value) ? value : 0).toFixed(2);
};

const toDateTimeLocal = (date: Date) => {
    const pad = (n: number) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
};

type OrderItemFormRow = {
    product_id: string;
    product_variant_id: string;
    quantity: number;
};

const form = useForm({
    first_name: '',
    last_name: '',
    email: '',
    phone: '',
    address: '',
    country: 'Nigeria',
    state: '',
    city: '',
    dispatch_location_id: props.dispatchLocations[0] ? String(props.dispatchLocations[0].id) : '',
    notes: '',
    status: 'processing',
    payment_method: 'cash',
    payment_reference: '',
    payment_status: 'paid',
    paid_at: toDateTimeLocal(new Date()),
    items: [
        {
            product_id: '',
            product_variant_id: '',
            quantity: 1,
        } satisfies OrderItemFormRow,
    ] as OrderItemFormRow[],
});

const findProduct = (productId: string) => {
    if (!productId) return null;
    const id = Number(productId);
    return props.products.find((p) => p.id === id) ?? null;
};

const findVariant = (product: Product | null, variantId: string) => {
    if (!product || !variantId) return null;
    const id = Number(variantId);
    return product.variants.find((v) => v.id === id) ?? null;
};

const rowPrice = (row: OrderItemFormRow) => {
    const product = findProduct(row.product_id);
    if (!product) return 0;
    const variant = findVariant(product, row.product_variant_id);
    const price = variant?.price ?? product.price;
    const value = Number(price ?? 0);
    return Number.isFinite(value) ? value : 0;
};

const rowTotal = (row: OrderItemFormRow) => rowPrice(row) * (Number(row.quantity) || 0);

const subtotal = computed(() => form.items.reduce((sum, row) => sum + rowTotal(row), 0));

const deliveryFee = computed(() => {
    const selected = props.dispatchLocations.find((l) => String(l.id) === String(form.dispatch_location_id));
    const value = selected ? Number(selected.price) : 0;
    return Number.isFinite(value) ? value : 0;
});

const total = computed(() => subtotal.value + deliveryFee.value);

const allErrors = computed(() => Object.values(form.errors));
const hasErrors = computed(() => allErrors.value.length > 0);
const clearAllErrors = () => form.clearErrors();

const getItemError = (index: number, field: 'product_id' | 'product_variant_id' | 'quantity') => {
    const errors = form.errors as Record<string, string>;
    return errors[`items.${index}.${field}`];
};

const addItem = () => {
    form.items.push({
        product_id: '',
        product_variant_id: '',
        quantity: 1,
    });
};

const removeItem = (index: number) => {
    form.items.splice(index, 1);
};

const onProductChanged = (index: number) => {
    form.items[index].product_variant_id = '';
};

const submit = () => {
    clearAllErrors();
    form.transform((data) => ({
        ...data,
        paid_at: data.payment_status === 'paid' ? data.paid_at : null,
        items: data.items.map((row) => {
            const product = findProduct(row.product_id);
            const variant = findVariant(product, row.product_variant_id);
            return {
                ...row,
                variant_name: variant?.variant_name ?? null,
            };
        }),
    })).post('/admin/orders', {
        onSuccess: () => toast.success('Order created successfully.'),
        onError: () => toast.error('Please fix the errors and try again.'),
    });
};
</script>

<template>

    <Head title="Create Order" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <ReceiptText class="h-6 w-6" />
                    <h1 class="text-3xl font-bold tracking-tight">New Order</h1>
                </div>
                <p class="text-muted-foreground">Create an order from the admin panel.</p>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/orders">
                        <ArrowLeft class="h-4 w-4 mr-2" />
                        Back
                    </Link>
                </Button>
                <Button :disabled="form.processing" @click="submit">
                    <Loader2 v-if="form.processing" class="h-4 w-4 mr-2 animate-spin" />
                    <Save v-else class="h-4 w-4 mr-2" />
                    {{ form.processing ? 'Creating…' : 'Create' }}
                </Button>
            </div>
        </div>

        <div v-if="hasErrors" class="bg-destructive/10 border border-destructive/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <AlertCircle class="h-5 w-5 text-destructive flex-shrink-0 mt-0.5" />
                <div class="flex-1">
                    <p class="font-medium text-destructive">Please fix the following errors:</p>
                    <ul class="mt-2 text-sm text-destructive/80 space-y-1">
                        <li v-for="(error, index) in allErrors" :key="index" class="flex items-start gap-2">
                            <span class="text-destructive">•</span>
                            <span>{{ error }}</span>
                        </li>
                    </ul>
                </div>
                <button type="button" @click="clearAllErrors" class="text-destructive hover:text-destructive/80">
                    <X class="h-4 w-4" />
                </button>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Customer</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="first_name">First name</Label>
                        <Input id="first_name" v-model="form.first_name"
                            :class="{ 'border-destructive': form.errors.first_name }" />
                        <p v-if="form.errors.first_name" class="text-sm text-destructive">{{ form.errors.first_name }}
                        </p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="last_name">Last name</Label>
                        <Input id="last_name" v-model="form.last_name"
                            :class="{ 'border-destructive': form.errors.last_name }" />
                        <p v-if="form.errors.last_name" class="text-sm text-destructive">{{ form.errors.last_name }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="email">Email</Label>
                        <Input id="email" v-model="form.email" type="email"
                            :class="{ 'border-destructive': form.errors.email }" />
                        <p v-if="form.errors.email" class="text-sm text-destructive">{{ form.errors.email }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="phone">Phone</Label>
                        <Input id="phone" v-model="form.phone" :class="{ 'border-destructive': form.errors.phone }" />
                        <p v-if="form.errors.phone" class="text-sm text-destructive">{{ form.errors.phone }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Shipping</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="address">Address</Label>
                        <Input id="address" v-model="form.address"
                            :class="{ 'border-destructive': form.errors.address }" />
                        <p v-if="form.errors.address" class="text-sm text-destructive">{{ form.errors.address }}</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="grid gap-2">
                            <Label for="country">Country</Label>
                            <Input id="country" v-model="form.country"
                                :class="{ 'border-destructive': form.errors.country }" />
                            <p v-if="form.errors.country" class="text-sm text-destructive">{{ form.errors.country }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="state">State</Label>
                            <Input id="state" v-model="form.state"
                                :class="{ 'border-destructive': form.errors.state }" />
                            <p v-if="form.errors.state" class="text-sm text-destructive">{{ form.errors.state }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="city">City</Label>
                            <Input id="city" v-model="form.city" :class="{ 'border-destructive': form.errors.city }" />
                            <p v-if="form.errors.city" class="text-sm text-destructive">{{ form.errors.city }}</p>
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label>Dispatch location</Label>
                        <Select v-model="form.dispatch_location_id">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.dispatch_location_id }">
                                <SelectValue placeholder="Select dispatch location" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="loc in dispatchLocations" :key="loc.id" :value="String(loc.id)">
                                    {{ loc.name }} ({{ formatMoney(loc.price) }})
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.dispatch_location_id" class="text-sm text-destructive">{{
                            form.errors.dispatch_location_id }}</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <Card>
            <CardHeader>
                <div class="flex items-center justify-between gap-4">
                    <CardTitle>Items</CardTitle>
                    <Button variant="outline" size="sm" @click="addItem">
                        <Plus class="h-4 w-4 mr-2" />
                        Add item
                    </Button>
                </div>
            </CardHeader>
            <CardContent class="grid gap-4">
                <p v-if="form.errors.items" class="text-sm text-destructive">{{ form.errors.items }}</p>

                <div v-for="(row, index) in form.items" :key="index" class="grid gap-4 md:grid-cols-12 items-end">
                    <div class="md:col-span-5 grid gap-2">
                        <Label>Product</Label>
                        <Select v-model="row.product_id" @update:model-value="() => onProductChanged(index)">
                            <SelectTrigger :class="{ 'border-destructive': !!getItemError(index, 'product_id') }">
                                <SelectValue placeholder="Select product" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="p in products" :key="p.id" :value="String(p.id)">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-7 w-7 overflow-hidden rounded bg-muted flex items-center justify-center">
                                            <img v-if="firstImage(p.images)" :src="firstImage(p.images) || ''"
                                                class="h-7 w-7 object-cover" loading="lazy" @error="hideBrokenImage" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate">{{ p.name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ formatMoney(p.price) }} · Stock {{ p.stock_count }}
                                            </div>
                                        </div>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="getItemError(index, 'product_id')" class="text-sm text-destructive">
                            {{ getItemError(index, 'product_id') }}
                        </p>
                    </div>

                    <div class="md:col-span-3 grid gap-2">
                        <Label>Variant</Label>
                        <Select v-model="row.product_variant_id"
                            :disabled="!(findProduct(row.product_id)?.variants?.length)">
                            <SelectTrigger
                                :class="{ 'border-destructive': !!getItemError(index, 'product_variant_id') }">
                                <SelectValue placeholder="No variants" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="v in (findProduct(row.product_id)?.variants ?? [])" :key="v.id"
                                    :value="String(v.id)">
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-6 w-6 overflow-hidden rounded bg-muted flex items-center justify-center">
                                            <img v-if="firstImage(v.images)" :src="firstImage(v.images) || ''"
                                                class="h-6 w-6 object-cover" loading="lazy" @error="hideBrokenImage" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="truncate">{{ v.variant_name }}</div>
                                            <div class="text-xs text-muted-foreground">
                                                {{ formatMoney(v.price) }} · Stock {{ v.stock_count }}
                                            </div>
                                        </div>
                                    </div>
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="getItemError(index, 'product_variant_id')" class="text-sm text-destructive">
                            {{ getItemError(index, 'product_variant_id') }}
                        </p>
                    </div>

                    <div class="md:col-span-2 grid gap-2">
                        <Label>Qty</Label>
                        <Input v-model.number="row.quantity" type="number" min="1" max="10"
                            :class="{ 'border-destructive': !!getItemError(index, 'quantity') }" />
                        <p v-if="getItemError(index, 'quantity')" class="text-sm text-destructive">
                            {{ getItemError(index, 'quantity') }}
                        </p>
                    </div>

                    <div class="md:col-span-1 text-sm tabular-nums">
                        <div class="text-muted-foreground">Total</div>
                        <div class="font-medium">{{ formatMoney(rowTotal(row)) }}</div>
                    </div>

                    <div class="md:col-span-1 flex justify-end">
                        <Button variant="outline" size="icon" :disabled="form.items.length === 1"
                            @click="removeItem(index)">
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </div>
                </div>

                <div class="flex justify-end">
                    <div class="w-full max-w-sm space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Subtotal</span>
                            <span class="tabular-nums">{{ formatMoney(subtotal) }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">Delivery</span>
                            <span class="tabular-nums">{{ formatMoney(deliveryFee) }}</span>
                        </div>
                        <div class="flex items-center justify-between border-t pt-2">
                            <span class="font-medium">Total</span>
                            <span class="font-medium tabular-nums">{{ formatMoney(total) }}</span>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div class="grid gap-4 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Order</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger :class="{ 'border-destructive': form.errors.status }">
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
                        <p v-if="form.errors.status" class="text-sm text-destructive">{{ form.errors.status }}</p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Notes</Label>
                        <Input id="notes" v-model="form.notes" :class="{ 'border-destructive': form.errors.notes }" />
                        <p v-if="form.errors.notes" class="text-sm text-destructive">{{ form.errors.notes }}</p>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Payment</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4">
                    <div class="grid gap-2">
                        <Label for="payment_method">Method</Label>
                        <Input id="payment_method" v-model="form.payment_method"
                            :class="{ 'border-destructive': form.errors.payment_method }" />
                        <p v-if="form.errors.payment_method" class="text-sm text-destructive">{{
                            form.errors.payment_method }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="payment_reference">Reference</Label>
                        <Input id="payment_reference" v-model="form.payment_reference"
                            :class="{ 'border-destructive': form.errors.payment_reference }" />
                        <p v-if="form.errors.payment_reference" class="text-sm text-destructive">{{
                            form.errors.payment_reference }}</p>
                    </div>
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label>Status</Label>
                            <Select v-model="form.payment_status">
                                <SelectTrigger :class="{ 'border-destructive': form.errors.payment_status }">
                                    <SelectValue placeholder="Select payment status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="processing">Processing</SelectItem>
                                    <SelectItem value="paid">Paid</SelectItem>
                                    <SelectItem value="failed">Failed</SelectItem>
                                </SelectContent>
                            </Select>
                            <p v-if="form.errors.payment_status" class="text-sm text-destructive">{{
                                form.errors.payment_status }}</p>
                        </div>
                        <div class="grid gap-2">
                            <Label for="paid_at">Paid at</Label>
                            <Input id="paid_at" v-model="form.paid_at" type="datetime-local"
                                :disabled="form.payment_status !== 'paid'"
                                :class="{ 'border-destructive': form.errors.paid_at }" />
                            <p v-if="form.errors.paid_at" class="text-sm text-destructive">{{ form.errors.paid_at }}</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
