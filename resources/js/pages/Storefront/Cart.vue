<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, reactive, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/StorefrontLayout.vue';
import PaymentModal from '@/components/PaymentModal.vue';
import { useCart } from '@/composables/useCart';

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    images: string[] | null;
    is_active: boolean;
    stock_count: number;
    category: { id: number; name: string; slug: string } | null;
}

interface Variant {
    id: number;
    sku: string;
    variant_name: string;
    price: number | null;
    stock_count: number;
    images: string[] | null;
}

interface CartItem {
    id: number;
    product_id: number;
    product_variant_id: number | null;
    variant_name: string | null;
    quantity: number;
    price: number;
    total: number;
    product: Product;
    variant: Variant | null;
}

interface Cart {
    items: CartItem[];
    total_items: number;
    subtotal: number;
}

interface DispatchLocation {
    id: number;
    name: string;
    price: number | string;
}

interface OrderItem {
    id: number;
    product: {
        id: number;
        name: string;
        images: string[] | null;
    };
    quantity: number;
    price: number;
    total: number;
}

interface Order {
    id: number;
    order_number: string;
    total: number;
    email: string;
    first_name: string;
    last_name: string;
    phone: string;
    items: OrderItem[];
}

interface Props {
    cart: Cart;
    dispatchLocations: DispatchLocation[];
    order?: Order;
}

const props = defineProps<Props>();
const page = usePage();
const { updateQuantity: updateCartQuantity, removeItem: removeFromCart, clearCart: clearCartGlobal, fetchCartCount, isLoading } = useCart();

const processingItems = ref<Set<string>>(new Set());
const isSubmitting = ref(false);
const showCheckoutForm = ref(false);
const showPaymentModal = ref(false);
const pendingOrder = ref<Order | null>(null);

// Watch for order prop from server (after order creation)
watch(() => props.order, (newOrder) => {
    if (newOrder) {
        pendingOrder.value = newOrder;
        showCheckoutForm.value = false;
        showPaymentModal.value = true;
    }
});

// Pre-fill form if user is logged in
const auth = computed(() => page.props.auth);
const form = reactive({
    first_name: auth.value?.user?.name?.split(' ')[0] || '',
    last_name: auth.value?.user?.name?.split(' ').slice(1).join(' ') || '',
    email: auth.value?.user?.email || '',
    phone: '',
    address: '',
    country: 'Nigeria',
    state: 'Abia',
    city: '',
    dispatch_location_id: props.dispatchLocations?.[0]?.id ?? null,
    notes: '',
});

const errors = ref<Record<string, string>>({});

const formatPrice = (price: number | string) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(Number(price));
};

const dispatchFee = computed(() => {
    const id = form.dispatch_location_id;
    if (!id) return 0;
    const loc = props.dispatchLocations.find((l) => l.id === id);
    return Number(loc?.price ?? 0);
});

const checkoutTotal = computed(() => Number(props.cart.subtotal) + Number(dispatchFee.value));

const updateQuantity = async (productId: number, quantity: number, variantId: number | null = null) => {
    const key = variantId ? `${productId}-${variantId}` : `${productId}`;
    processingItems.value.add(key);
    try {
        await updateCartQuantity(productId, quantity, variantId);
    } finally {
        processingItems.value.delete(key);
    }
};

const incrementQuantity = (item: CartItem) => {
    const maxStock = item.variant ? item.variant.stock_count : item.product.stock_count;
    if (item.quantity < 10 && item.quantity < maxStock) {
        updateQuantity(item.product_id, item.quantity + 1, item.product_variant_id);
    }
};

const decrementQuantity = (item: CartItem) => {
    if (item.quantity > 1) {
        updateQuantity(item.product_id, item.quantity - 1, item.product_variant_id);
    } else {
        removeItem(item.product_id, item.product_variant_id);
    }
};

const removeItem = async (productId: number, variantId: number | null = null) => {
    const key = variantId ? `${productId}-${variantId}` : `${productId}`;
    processingItems.value.add(key);
    try {
        await removeFromCart(productId, variantId);
    } finally {
        processingItems.value.delete(key);
    }
};

const clearCart = async () => {
    if (confirm('Are you sure you want to clear your cart?')) {
        await clearCartGlobal();
        router.reload({ only: ['cart'] });
    }
};

const isItemProcessing = (productId: number, variantId: number | null = null) => {
    const key = variantId ? `${productId}-${variantId}` : `${productId}`;
    return processingItems.value.has(key);
};

const hasUnavailableItems = computed(() => {
    return props.cart.items.some((item) => {
        if (item.variant) {
            return !item.product.is_active || item.variant.stock_count < item.quantity;
        }
        return !item.product.is_active || item.product.stock_count < item.quantity;
    });
});

const getStockWarning = (item: CartItem): string | null => {
    if (!item.product.is_active) return 'This product is no longer available';
    const stockCount = item.variant ? item.variant.stock_count : item.product.stock_count;
    if (stockCount === 0) return 'This product is out of stock';
    if (stockCount < item.quantity) {
        return `Only ${stockCount} available. Please reduce quantity.`;
    }
    return null;
};

const submitOrder = async () => {
    if (hasUnavailableItems.value) {
        return;
    }

    isSubmitting.value = true;
    errors.value = {};

    try {
        await router.post('/order', form, {
            onSuccess: () => {
                // Order created - payment modal will be shown via watch
            },
            onError: (errs) => {
                errors.value = errs;
            },
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } catch (e) {
        isSubmitting.value = false;
    }
};

const handlePaymentClose = () => {
    showPaymentModal.value = false;
    // Reload cart to show it's been cleared
    router.reload({ only: ['cart'] });
};

const handlePaymentPaid = () => {
    showPaymentModal.value = false;
    router.visit('/order/success');
};
</script>

<template>

    <Head title="Shopping Cart" />
    <AppLayout>
        <div class="max-w-7xl mx-auto px-4 py-8">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm text-muted-foreground mb-6">
                <Link href="/" class="hover:text-primary transition-colors">Home</Link>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-foreground font-medium">Cart</span>
            </nav>

            <h1 class="text-3xl font-bold text-foreground mb-8">Shopping Cart</h1>

            <div v-if="cart.items.length === 0" class="text-center py-16">
                <div class="w-32 h-32 mx-auto bg-secondary rounded-full flex items-center justify-center mb-6">
                    <svg class="w-16 h-16 text-muted-foreground/40" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-foreground mb-2">Your cart is empty</h3>
                <p class="text-muted-foreground mb-6">Looks like you haven't added anything to your cart yet.</p>
                <Link href="/">
                    <Button class="rounded-full px-8 shadow-sm hover:shadow-md transition-all">
                        Continue Shopping
                    </Button>
                </Link>
            </div>

            <div v-else class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2 space-y-4">
                    <!-- Warning for unavailable items -->
                    <div v-if="hasUnavailableItems"
                        class="bg-amber-50 border border-amber-200 rounded-2xl p-4 mb-4 shadow-sm">
                        <div class="flex items-center gap-2 text-amber-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span class="font-medium">Some items in your cart need attention</span>
                        </div>
                    </div>

                    <div v-for="item in cart.items" :key="item.id"
                        class="bg-card rounded-2xl shadow-sm hover:shadow-md transition-shadow overflow-hidden border border-border">
                        <div class="p-5">
                            <div class="flex gap-4">
                                <!-- Product Image -->
                                <Link :href="`/product/${item.product.slug}`" class="flex-shrink-0">
                                    <div class="w-24 h-24 bg-secondary rounded-xl overflow-hidden">
                                        <img v-if="item.product.images?.length" :src="item.product.images[0]"
                                            :alt="item.product.name" class="w-full h-full object-cover" />
                                        <div v-else
                                            class="w-full h-full flex items-center justify-center text-muted-foreground/40">
                                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                    </div>
                                </Link>

                                <!-- Product Details -->
                                <div class="flex-1 min-w-0">
                                    <Link :href="`/product/${item.product.slug}`">
                                        <h3
                                            class="font-semibold text-foreground hover:text-accent-foreground transition-colors truncate">
                                            {{ item.product.name }}
                                        </h3>
                                    </Link>
                                    <!-- Variant Name -->
                                    <p v-if="item.variant_name"
                                        class="text-sm text-accent-foreground font-medium mt-0.5">
                                        {{ item.variant_name }}
                                    </p>
                                    <p v-if="item.product.category" class="text-sm text-muted-foreground mt-0.5">
                                        {{ item.product.category.name }}
                                    </p>

                                    <!-- Stock Warning -->
                                    <p v-if="getStockWarning(item)" class="text-sm text-red-500 mt-1.5">
                                        {{ getStockWarning(item) }}
                                    </p>

                                    <!-- Price & Quantity -->
                                    <div class="flex items-center justify-between mt-3">
                                        <div class="flex items-center bg-secondary rounded-full">
                                            <button @click="decrementQuantity(item)"
                                                :disabled="isItemProcessing(item.product_id, item.product_variant_id)"
                                                class="p-2 hover:bg-accent disabled:opacity-50 rounded-full transition-colors">
                                                <svg class="w-4 h-4 text-muted-foreground" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </button>
                                            <span class="w-10 text-center font-semibold text-foreground">{{
                                                item.quantity
                                            }}</span>
                                            <button @click="incrementQuantity(item)"
                                                :disabled="isItemProcessing(item.product_id, item.product_variant_id) || item.quantity >= 10 || (item.variant ? item.quantity >= item.variant.stock_count : item.quantity >= item.product.stock_count)"
                                                class="p-2 hover:bg-accent disabled:opacity-50 rounded-full transition-colors">
                                                <svg class="w-4 h-4 text-muted-foreground" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                        <span class="text-lg font-bold text-foreground">
                                            {{ formatPrice(item.total) }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Remove Button -->
                                <button @click="removeItem(item.product_id, item.product_variant_id)"
                                    :disabled="isItemProcessing(item.product_id, item.product_variant_id)"
                                    class="text-muted-foreground/40 hover:text-destructive transition-colors p-1 self-start"
                                    title="Remove item">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Clear Cart -->
                    <div class="flex justify-end pt-2">
                        <Button variant="ghost" @click="clearCart"
                            class="text-destructive hover:text-destructive hover:bg-destructive/10 rounded-full">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Clear Cart
                        </Button>
                    </div>
                </div>

                <!-- Order Summary & Checkout -->
                <div class="lg:col-span-1 space-y-6">
                    <!-- Order Summary -->
                    <div class="bg-card rounded-2xl shadow-sm border border-border">
                        <div class="p-6 border-b border-border">
                            <h2 class="text-lg font-bold text-foreground">Order Summary</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="flex justify-between text-muted-foreground">
                                <span>Items ({{ cart.total_items }})</span>
                                <span class="font-medium text-foreground">{{ formatPrice(cart.subtotal) }}</span>
                            </div>
                            <div class="flex justify-between text-muted-foreground">
                                <span>Dispatch</span>
                                <span class="font-medium">Selected at checkout</span>
                            </div>
                            <div class="border-t border-border pt-4">
                                <div class="flex justify-between text-xl font-bold text-foreground">
                                    <span>Total</span>
                                    <span>{{ formatPrice(cart.subtotal) }}</span>
                                </div>
                            </div>
                            <Button @click="showCheckoutForm = true"
                                class="w-full rounded-full shadow-sm hover:shadow-md transition-all" size="lg"
                                :disabled="hasUnavailableItems">
                                Proceed to Checkout
                            </Button>
                            <Link href="/" class="block mt-3">
                                <Button variant="outline" size="lg"
                                    class="w-full rounded-full border-2 border-border text-foreground hover:border-primary hover:bg-secondary hover:text-primary font-medium transition-all duration-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                                    </svg>
                                    Continue Shopping
                                </Button>
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Checkout Modal -->
            <div v-if="showCheckoutForm"
                class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                @click.self="showCheckoutForm = false">
                <div
                    class="bg-card rounded-3xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden border border-border">
                    <!-- Modal Header -->
                    <div class="bg-secondary px-6 py-5 text-foreground border-b border-border">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold">Checkout</h2>
                                <p class="text-sm mt-1 text-muted-foreground">Complete your order</p>
                            </div>
                            <button @click="showCheckoutForm = false"
                                class="text-muted-foreground hover:text-foreground transition-colors p-1">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Order Summary Bar -->
                    <div class="bg-secondary px-6 py-4 border-b border-border">
                        <div class="space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Items ({{ cart.total_items }})</span>
                                <span class="font-medium text-foreground">{{ formatPrice(cart.subtotal) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Dispatch</span>
                                <span class="font-medium text-foreground">{{ formatPrice(dispatchFee) }}</span>
                            </div>
                            <div class="pt-2 border-t border-border flex items-center justify-between">
                                <span class="text-foreground font-semibold">Total</span>
                                <span class="text-xl font-bold text-foreground">{{ formatPrice(checkoutTotal) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <form @submit.prevent="submitOrder" class="p-6 space-y-6 overflow-y-auto max-h-[60vh]">
                        <!-- Personal Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                                Personal Information
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">First Name *</label>

                                    <input v-model="form.first_name" type="text" placeholder="Enter first name"
                                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    <p v-if="errors.first_name" class="text-red-500 text-xs mt-1">{{ errors.first_name
                                        }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Last Name *</label>
                                    <input v-model="form.last_name" type="text" placeholder="Enter last name"
                                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    <p v-if="errors.last_name" class="text-red-500 text-xs mt-1">{{ errors.last_name }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                                Contact Information
                            </h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Phone Number
                                        *</label>
                                    <input v-model="form.phone" type="tel" placeholder="e.g. 08012345678"
                                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    <p v-if="errors.phone" class="text-red-500 text-xs mt-1">{{ errors.phone }}</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Email Address
                                        *</label>
                                    <input v-model="form.email" type="email" placeholder="your@email.com"
                                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    <p v-if="errors.email" class="text-red-500 text-xs mt-1">{{ errors.email }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div>
                            <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Shipping Address
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-foreground mb-1.5">Street Address
                                        *</label>
                                    <input v-model="form.address" type="text" placeholder="Enter your full address"
                                        class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    <p v-if="errors.address" class="text-red-500 text-xs mt-1">{{ errors.address }}</p>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">Country</label>
                                        <select v-model="form.country"
                                            class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30">
                                            <option value="Nigeria">Nigeria</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">State</label>
                                        <select v-model="form.state"
                                            class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30">
                                            <option value="Abia">Abia</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-foreground mb-1.5">City
                                            (Optional)</label>
                                        <input v-model="form.city" type="text" placeholder="e.g. Umuahia"
                                            class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold text-foreground mb-4 flex items-center gap-2">
                                <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 01.553-.894L9 2m0 18l6-3m-6 3V2m6 15l5.447-2.724A1 1 0 0021 13.382V2.618a1 1 0 00-.553-.894L15 0m0 17V0m0 0L9 2" />
                                </svg>
                                Dispatch Location
                            </h3>
                            <div>
                                <label class="block text-sm font-medium text-foreground mb-1.5">Location *</label>
                                <select v-model="form.dispatch_location_id"
                                    class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30">
                                    <option v-for="loc in dispatchLocations" :key="loc.id" :value="loc.id">
                                        {{ loc.name }} - {{ formatPrice(loc.price) }}
                                    </option>
                                </select>
                                <p v-if="errors.dispatch_location_id" class="text-red-500 text-xs mt-1">{{
                                    errors.dispatch_location_id }}</p>
                            </div>
                        </div>

                        <!-- Order Notes -->
                        <div>
                            <label class="block text-sm font-medium text-foreground mb-1.5">Order Notes
                                (Optional)</label>
                            <textarea v-model="form.notes" rows="3"
                                placeholder="Any special instructions for delivery..."
                                class="w-full rounded-xl border border-border bg-card px-4 py-3 text-foreground focus:border-primary focus:ring-2 focus:ring-primary/30"></textarea>
                        </div>

                        <!-- Error Message -->
                        <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-xl p-4">
                            <p class="text-red-600 text-sm">{{ errors.general }}</p>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <Button type="submit" :disabled="isSubmitting || hasUnavailableItems"
                                class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-lg font-semibold flex items-center justify-center">
                                <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-foreground"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4">
                                    </circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                {{ isSubmitting ? 'Processing...' : 'Proceed to Payment' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <PaymentModal v-if="pendingOrder" :order="pendingOrder" :visible="showPaymentModal" @close="handlePaymentClose"
            @paid="handlePaymentPaid" />
    </AppLayout>
</template>
