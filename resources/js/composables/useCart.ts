import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';

// Global reactive state - shared across all components
const cartCount = ref(0);
const isLoading = ref(false);

// Fetch cart count from server
const fetchCartCount = async () => {
    try {
        const response = await fetch('/cart/count', {
            headers: { 'Accept': 'application/json' },
        });
        const data = await response.json();
        cartCount.value = data.count;
    } catch (e) {
        console.error('Failed to fetch cart count', e);
    }
};

// Initialize cart count (call once on app mount)
let initialized = false;
const initializeCart = () => {
    if (!initialized) {
        fetchCartCount();
        initialized = true;
    }
};

export type UseCartReturn = {
    cartCount: typeof cartCount;
    isLoading: typeof isLoading;
    fetchCartCount: typeof fetchCartCount;
    initializeCart: typeof initializeCart;
    addToCart: (productId: number, quantity?: number, variantId?: number | null, variantName?: string | null) => Promise<void>;
    updateQuantity: (productId: number, quantity: number, variantId?: number | null) => Promise<void>;
    removeItem: (productId: number, variantId?: number | null) => Promise<void>;
    clearCart: () => Promise<void>;
};

export function useCart(): UseCartReturn {
    const addToCart = async (productId: number, quantity: number = 1, variantId: number | null = null, variantName: string | null = null): Promise<void> => {
        isLoading.value = true;

        const data: { product_id: number; quantity: number; product_variant_id?: number; variant_name?: string } = { 
            product_id: productId, 
            quantity,
        };
        
        if (variantId) {
            data.product_variant_id = variantId;
        }
        
        if (variantName) {
            data.variant_name = variantName;
        }

        return new Promise((resolve, reject) => {
            router.post(
                '/cart/add',
                data,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        // Update local count optimistically
                        cartCount.value += quantity;
                        // Then fetch actual count from server
                        fetchCartCount();
                        resolve();
                    },
                    onError: (errors) => {
                        console.error('Failed to add to cart', errors);
                        reject(errors);
                    },
                    onFinish: () => {
                        isLoading.value = false;
                    },
                }
            );
        });
    };

    const updateQuantity = async (productId: number, quantity: number, variantId: number | null = null): Promise<void> => {
        isLoading.value = true;

        const data = { 
            product_id: productId, 
            quantity,
            ...(variantId ? { product_variant_id: variantId } : {})
        };

        return new Promise((resolve, reject) => {
            router.put(
                '/cart/update',
                data,
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        fetchCartCount();
                        resolve();
                    },
                    onError: (errors) => {
                        console.error('Failed to update cart', errors);
                        reject(errors);
                    },
                    onFinish: () => {
                        isLoading.value = false;
                    },
                }
            );
        });
    };

    const removeItem = async (productId: number, variantId: number | null = null): Promise<void> => {
        isLoading.value = true;

        const data = { 
            product_id: productId,
            ...(variantId ? { product_variant_id: variantId } : {})
        };

        return new Promise((resolve, reject) => {
            router.delete('/cart/remove', {
                data,
                preserveScroll: true,
                onSuccess: () => {
                    fetchCartCount();
                    resolve();
                },
                onError: (errors) => {
                    console.error('Failed to remove item', errors);
                    reject(errors);
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        });
    };

    const clearCart = async (): Promise<void> => {
        isLoading.value = true;

        return new Promise((resolve, reject) => {
            router.delete('/cart/clear', {
                preserveScroll: true,
                onSuccess: () => {
                    cartCount.value = 0;
                    resolve();
                },
                onError: (errors) => {
                    console.error('Failed to clear cart', errors);
                    reject(errors);
                },
                onFinish: () => {
                    isLoading.value = false;
                },
            });
        });
    };

    return {
        cartCount,
        isLoading,
        fetchCartCount,
        initializeCart,
        addToCart,
        updateQuantity,
        removeItem,
        clearCart,
    };
}
