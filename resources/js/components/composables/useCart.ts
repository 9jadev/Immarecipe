import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

interface CartItem {
    id: number;
    product_id: number;
    quantity: number;
    price: number;
    total: number;
    product: {
        id: number;
        name: string;
        slug: string;
        price: number;
        images: string[] | null;
        is_active: boolean;
        stock_count: number;
        category: { id: number; name: string; slug: string } | null;
    };
}

interface Cart {
    items: CartItem[];
    total_items: number;
    subtotal: number;
}

const cart = ref<Cart>({
    items: [],
    total_items: 0,
    subtotal: 0,
});

const isInitialized = ref(false);

export function useCart() {
    const items = computed(() => cart.value.items);
    const totalItems = computed(() => cart.value.total_items);
    const subtotal = computed(() => cart.value.subtotal);
    const isEmpty = computed(() => cart.value.items.length === 0);

    const initCart = (cartData: Cart) => {
        cart.value = cartData;
        isInitialized.value = true;
    };

    const addToCart = async (productId: number, quantity: number = 1) => {
        return new Promise((resolve, reject) => {
            router.post(
                '/cart/add',
                { product_id: productId, quantity },
                {
                    preserveScroll: true,
                    onSuccess: (page) => {
                        // Update cart from response if available
                        const flash = page.props.flash as { success?: string; error?: string };
                        if (flash?.error) {
                            reject(new Error(flash.error));
                        } else {
                            refreshCart();
                            resolve(flash?.success);
                        }
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                }
            );
        });
    };

    const updateQuantity = async (productId: number, quantity: number) => {
        return new Promise((resolve, reject) => {
            router.put(
                '/cart/update',
                { product_id: productId, quantity },
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        refreshCart();
                        resolve(true);
                    },
                    onError: (errors) => {
                        reject(errors);
                    },
                }
            );
        });
    };

    const removeItem = async (productId: number) => {
        return new Promise((resolve, reject) => {
            router.delete('/cart/remove', {
                data: { product_id: productId },
                preserveScroll: true,
                onSuccess: () => {
                    refreshCart();
                    resolve(true);
                },
                onError: (errors) => {
                    reject(errors);
                },
            });
        });
    };

    const clearCart = async () => {
        return new Promise((resolve, reject) => {
            router.delete('/cart/clear', {
                preserveScroll: true,
                onSuccess: () => {
                    cart.value = { items: [], total_items: 0, subtotal: 0 };
                    resolve(true);
                },
                onError: (errors) => {
                    reject(errors);
                },
            });
        });
    };

    const refreshCart = async () => {
        try {
            const response = await fetch('/cart/count', {
                headers: {
                    'Accept': 'application/json',
                },
            });
            const data = await response.json();
            cart.value.total_items = data.count;
        } catch (error) {
            console.error('Failed to refresh cart:', error);
        }
    };

    const formatPrice = (price: number) => {
        return new Intl.NumberFormat('en-NG', {
            style: 'currency',
            currency: 'NGN',
            minimumFractionDigits: 0,
        }).format(price);
    };

    return {
        cart,
        items,
        totalItems,
        subtotal,
        isEmpty,
        initCart,
        addToCart,
        updateQuantity,
        removeItem,
        clearCart,
        refreshCart,
        formatPrice,
    };
}
