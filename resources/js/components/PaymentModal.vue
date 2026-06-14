<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';

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
    order: Order;
    visible: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'paid'): void;
}>();

const selectedMethod = ref<'flutterwave' | 'safe_haven' | null>(null);
const isProcessing = ref(false);
const error = ref<string | null>(null);

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(price);
};

const customerName = computed(() => `${props.order.first_name} ${props.order.last_name}`);

const initializePayment = async () => {
    if (!selectedMethod.value) {
        error.value = 'Please select a payment method';
        return;
    }

    isProcessing.value = true;
    error.value = null;

    try {
        const response = await fetch('/payment/initialize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({
                order_id: props.order.id,
                payment_method: selectedMethod.value,
            }),
        });

        const data = await response.json();

        if (response.ok && data.payment_url) {
            // Redirect to payment gateway
            window.location.href = data.payment_url;
        } else {
            error.value = data.error || 'Failed to initialize payment';
            isProcessing.value = false;
        }
    } catch (e) {
        error.value = 'An error occurred. Please try again.';
        isProcessing.value = false;
    }
};

const handleClose = () => {
    if (!isProcessing.value) {
        emit('close');
    }
};
</script>

<template>
    <div v-if="visible" class="fixed inset-0 bg-black/60 backdrop-blur-sm z-50 flex items-center justify-center p-4"
        @click.self="handleClose">
        <div class="bg-card rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden border border-border">
            <!-- Header -->
            <div class="bg-secondary px-6 py-5 text-foreground border-b border-border">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold">Complete Payment</h2>
                        <p class="text-sm mt-1 text-muted-foreground">Order #{{ order.order_number }}</p>
                    </div>
                    <button @click="handleClose" :disabled="isProcessing"
                        class="text-muted-foreground hover:text-foreground transition-colors p-1 disabled:opacity-50">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="bg-secondary px-6 py-4 border-b border-border">
                <div class="flex justify-between items-center">
                    <div>
                        <p class="text-sm text-muted-foreground">Amount to Pay</p>
                        <p class="text-2xl font-bold text-foreground">{{ formatPrice(order.total) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">{{ order.items.length }} item(s)</p>
                    </div>
                </div>
            </div>

            <!-- Payment Methods -->
            <div class="p-6 space-y-4">
                <h3 class="text-sm font-semibold text-foreground uppercase tracking-wide">Select Payment Method</h3>

                <!-- Flutterwave Option -->
                <button @click="selectedMethod = 'flutterwave'" :disabled="isProcessing" :class="[
                    'w-full p-4 rounded-2xl border-2 transition-all text-left',
                    selectedMethod === 'flutterwave'
                        ? 'border-primary bg-secondary'
                        : 'border-border hover:border-ring'
                ]">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-secondary rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-foreground" viewBox="0 0 24 24" fill="currentColor">
                                <path
                                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-foreground">Flutterwave</p>
                            <p class="text-sm text-muted-foreground">Pay with Card, Bank Transfer, USSD</p>
                        </div>
                        <div v-if="selectedMethod === 'flutterwave'" class="ml-auto">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </button>

                <!-- Safe Haven Option -->
                <button @click="selectedMethod = 'safe_haven'" :disabled="isProcessing" :class="[
                    'w-full p-4 rounded-2xl border-2 transition-all text-left',
                    selectedMethod === 'safe_haven'
                        ? 'border-primary bg-secondary'
                        : 'border-border hover:border-ring'
                ]">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 bg-secondary rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-accent-foreground" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <div>
                            <p class="font-semibold text-foreground">Safe Haven</p>
                            <p class="text-sm text-muted-foreground">Pay via Safe Haven MFB</p>
                        </div>
                        <div v-if="selectedMethod === 'safe_haven'" class="ml-auto">
                            <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </button>

                <!-- Error Message -->
                <div v-if="error" class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <p class="text-red-600 text-sm">{{ error }}</p>
                </div>

                <!-- Pay Button -->
                <Button @click="initializePayment" :disabled="!selectedMethod || isProcessing"
                    class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-lg font-semibold">
                    <svg v-if="isProcessing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-primary-foreground"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ isProcessing ? 'Processing...' : `Pay ${formatPrice(order.total)}` }}
                </Button>

                <!-- Customer Info -->
                <div class="mt-4 pt-4 border-t border-border">
                    <p class="text-xs text-muted-foreground text-center">
                        Payment details will be sent to <span class="font-medium">{{ order.email }}</span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
