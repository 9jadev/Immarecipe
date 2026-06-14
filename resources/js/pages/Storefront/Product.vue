<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/StorefrontLayout.vue';
import { useCart } from '@/composables/useCart';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface VariantValue {
    id: number;
    value: string;
}

interface VariantOption {
    id: number;
    name: string;
    values: VariantValue[];
}

interface VariantItem {
    id: number;
    sku: string;
    variant_name: string;
    price: number | null;
    compare_price: number | null;
    stock_count: number;
    stock_status: string;
    is_active: boolean;
    variant_values: { id: number; pivot: { variant_value_id: number } }[];
}

interface Product {
    id: number;
    name: string;
    slug: string;
    description: string;
    short_description: string | null;
    price: number;
    compare_price: number | null;
    images: string[] | null;
    category: Category | null;
    stock_count: number;
    is_on_sale: boolean;
    discount_percentage: number | null;
    weight: number | null;
    weight_unit: string | null;
    variantOptions?: VariantOption[];
    variants?: VariantItem[];
}

interface Props {
    product: Product;
    relatedProducts: Product[];
}

const props = defineProps<Props>();
const { addToCart: addToCartGlobal, isLoading } = useCart();

const quantity = ref(1);
const selectedImage = ref(0);
const selectedVariantValues = ref<Record<string, number>>({});

// Check if product has variants
const hasVariants = computed(() => {
    return props.product.variantOptions && props.product.variantOptions.length > 0;
});

// Initialize selected variant values
if (hasVariants.value && props.product.variantOptions) {
    props.product.variantOptions.forEach(option => {
        if (option.values.length > 0) {
            selectedVariantValues.value[option.name] = option.values[0].id;
        }
    });
}

// Find the selected variant based on selected values
const selectedVariant = computed(() => {
    if (!hasVariants.value || !props.product.variants) return null;

    const selectedValueIds = Object.values(selectedVariantValues.value);

    return props.product.variants.find(variant => {
        const variantValueIds = variant.variant_values.map(vv => vv.pivot.variant_value_id);
        return selectedValueIds.every(id => variantValueIds.includes(id)) &&
            variantValueIds.every(id => selectedValueIds.includes(id));
    }) || null;
});

// Get effective price (variant or product)
const effectivePrice = computed(() => {
    if (selectedVariant.value && selectedVariant.value.price !== null) {
        return selectedVariant.value.price;
    }
    return props.product.price;
});

// Get effective compare price
const effectiveComparePrice = computed(() => {
    if (selectedVariant.value && selectedVariant.value.compare_price !== null) {
        return selectedVariant.value.compare_price;
    }
    return props.product.compare_price;
});

// Get effective stock
const effectiveStock = computed(() => {
    if (selectedVariant.value) {
        return selectedVariant.value.stock_count;
    }
    if (hasVariants.value && props.product.variants) {
        // Sum all variant stock if no variant selected yet
        return props.product.variants.reduce((sum, v) => sum + v.stock_count, 0);
    }
    return props.product.stock_count;
});

// Check if selected variant is in stock
const isInStock = computed(() => {
    if (hasVariants.value && !selectedVariant.value) {
        return false; // Need to select a variant
    }
    if (selectedVariant.value) {
        return selectedVariant.value.stock_count > 0 && selectedVariant.value.is_active;
    }
    return props.product.stock_count > 0;
});

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(price);
};

const addToCart = async () => {
    try {
        const variantId = selectedVariant.value?.id || null;
        const variantName = selectedVariant.value?.variant_name || null;
        await addToCartGlobal(props.product.id, quantity.value, variantId, variantName);
    } catch (e) {
        console.error('Failed to add to cart', e);
    }
};

const incrementQuantity = () => {
    const maxStock = selectedVariant.value ? selectedVariant.value.stock_count : props.product.stock_count;
    if (quantity.value < 10 && quantity.value < maxStock) {
        quantity.value++;
    }
};

const decrementQuantity = () => {
    if (quantity.value > 1) {
        quantity.value--;
    }
};
</script>

<template>

    <Head :title="product.name" />
    <AppLayout>
        <!-- Breadcrumb -->
        <div class="max-w-7xl mx-auto px-4 pt-6">
            <nav class="flex items-center gap-2 text-sm text-muted-foreground">
                <Link href="/" class="hover:text-primary transition-colors">Home</Link>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <Link v-if="product.category" :href="`/?category=${product.category.slug}`"
                    class="hover:text-primary transition-colors">
                    {{ product.category.name }}
                </Link>
                <svg v-if="product.category" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
                <span class="text-foreground font-medium">{{ product.name }}</span>
            </nav>
        </div>

        <!-- Product Detail -->
        <div class="max-w-7xl mx-auto px-4 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <!-- Images -->
                <div class="space-y-4">
                    <!-- Main Image -->
                    <div class="aspect-square bg-card rounded-2xl shadow-sm overflow-hidden border border-border">
                        <img v-if="product.images?.length" :src="product.images[selectedImage]" :alt="product.name"
                            class="w-full h-full object-cover" />
                        <div v-else class="w-full h-full flex items-center justify-center bg-secondary">
                            <svg class="w-24 h-24 text-muted-foreground/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                    </div>
                    <!-- Thumbnails -->
                    <div v-if="product.images && product.images.length > 1" class="flex gap-3 overflow-x-auto pb-2">
                        <button v-for="(image, index) in product.images" :key="index" @click="selectedImage = index"
                            :class="[
                                'w-20 h-20 rounded-xl overflow-hidden border-2 flex-shrink-0 transition-all',
                                selectedImage === index ? 'border-primary ring-2 ring-primary/30' : 'border-border hover:border-ring'
                            ]">
                            <img :src="image" :alt="`${product.name} ${index + 1}`"
                                class="w-full h-full object-cover" />
                        </button>
                    </div>
                </div>

                <!-- Details -->
                <div class="space-y-6">
                    <!-- Category & Title -->
                    <div>
                        <p v-if="product.category" class="text-sm text-accent-foreground font-medium mb-2">
                            {{ product.category.name }}
                        </p>
                        <h1 class="text-3xl font-bold text-foreground mb-3">
                            {{ product.name }}
                        </h1>
                        <div class="flex items-center gap-2">
                            <span v-if="effectiveComparePrice && effectiveComparePrice > effectivePrice"
                                class="inline-flex items-center px-3 py-1 bg-destructive text-destructive-foreground text-xs font-bold rounded-full">
                                -{{ Math.round((1 - effectivePrice / effectiveComparePrice) * 100) }}% OFF
                            </span>
                            <span v-if="!hasVariants && effectiveStock <= 5 && effectiveStock > 0"
                                class="inline-flex items-center px-3 py-1 bg-secondary text-accent-foreground text-xs font-semibold rounded-full">
                                Only {{ effectiveStock }} left!
                            </span>
                            <span v-else-if="!hasVariants && effectiveStock === 0"
                                class="inline-flex items-center px-3 py-1 bg-destructive/15 text-destructive text-xs font-semibold rounded-full">
                                Out of Stock
                            </span>
                            <span v-else-if="!hasVariants"
                                class="inline-flex items-center px-3 py-1 bg-secondary text-secondary-foreground text-xs font-semibold rounded-full">
                                In Stock
                            </span>
                            <span
                                v-if="selectedVariant && selectedVariant.stock_count <= 5 && selectedVariant.stock_count > 0"
                                class="inline-flex items-center px-3 py-1 bg-secondary text-accent-foreground text-xs font-semibold rounded-full">
                                Only {{ selectedVariant.stock_count }} left!
                            </span>
                        </div>
                    </div>

                    <!-- Price -->
                    <div class="bg-secondary rounded-2xl p-5">
                        <div class="flex items-baseline gap-3">
                            <span class="text-3xl font-bold text-foreground">
                                {{ formatPrice(effectivePrice) }}
                            </span>
                            <span v-if="effectiveComparePrice" class="text-xl text-muted-foreground line-through">
                                {{ formatPrice(effectiveComparePrice) }}
                            </span>
                        </div>
                        <p v-if="effectiveComparePrice && effectiveComparePrice > effectivePrice"
                            class="text-sm text-accent-foreground mt-1">
                            You save {{ formatPrice(effectiveComparePrice - effectivePrice) }}!
                        </p>
                    </div>
                    <div v-if="hasVariants && product.variantOptions" class="space-y-4">
                        <div v-for="option in product.variantOptions" :key="option.id" class="space-y-2">
                            <div v-if="hasVariants" class="space-y-4"></div>
                            <label class="text-sm font-medium text-foreground">{{ option.name }}</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="value in option.values" :key="value.id"
                                    @click="selectedVariantValues[option.name] = value.id" :class="[
                                        'px-4 py-2 rounded-lg border-2 text-sm font-medium transition-all',
                                        selectedVariantValues[option.name] === value.id
                                            ? 'border-primary bg-secondary text-foreground'
                                            : 'border-border bg-card text-foreground hover:border-ring'
                                    ]">
                                    {{ value.value }}
                                </button>
                            </div>
                        </div>

                        <!-- Selected variant info -->
                        <div v-if="selectedVariant" class="flex items-center gap-3 text-sm">
                            <span class="text-muted-foreground">Selected:</span>
                            <span class="font-medium text-foreground">{{ selectedVariant.variant_name }}</span>
                            <span v-if="selectedVariant.stock_count === 0"
                                class="px-2 py-0.5 bg-destructive/15 text-destructive text-xs font-semibold rounded-full">
                                Out of Stock
                            </span>
                            <span v-else-if="selectedVariant.stock_count <= 5"
                                class="px-2 py-0.5 bg-secondary text-accent-foreground text-xs font-semibold rounded-full">
                                {{ selectedVariant.stock_count }} left
                            </span>
                            <span v-else
                                class="px-2 py-0.5 bg-secondary text-secondary-foreground text-xs font-semibold rounded-full">
                                In Stock
                            </span>
                        </div>
                        <div v-else-if="hasVariants" class="text-sm text-accent-foreground">
                            Please select all options to see availability
                        </div>
                    </div>

                    <!-- Short Description -->
                    <p v-if="product.short_description" class="text-muted-foreground text-lg leading-relaxed">
                        {{ product.short_description }}
                    </p>

                    <!-- Weight -->
                    <p v-if="product.weight" class="flex items-center gap-2 text-sm text-muted-foreground">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                        </svg>
                        Weight: {{ product.weight }} {{ product.weight_unit }}
                    </p>

                    <!-- Quantity & Add to Cart -->
                    <div class="flex items-center gap-4">
                        <div class="flex items-center bg-secondary rounded-full">
                            <Button variant="ghost" size="icon-sm" @click="decrementQuantity" :disabled="quantity <= 1"
                                class="rounded-full hover:bg-accent">
                                <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M20 12H4" />
                                </svg>
                            </Button>
                            <span class="w-12 text-center font-semibold text-foreground">{{ quantity }}</span>
                            <Button variant="ghost" size="icon-sm" @click="incrementQuantity"
                                :disabled="quantity >= 10 || (selectedVariant ? quantity >= selectedVariant.stock_count : quantity >= effectiveStock)"
                                class="rounded-full hover:bg-accent">
                                <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                            </Button>
                        </div>
                        <Button
                            class="flex-1 rounded-full shadow-sm hover:shadow-md transition-all"
                            size="lg" @click="addToCart" :disabled="!isInStock || isLoading">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Add to Cart
                        </Button>
                    </div>

                    <!-- Full Description -->
                    <div v-if="product.description" class="pt-6 border-t border-border">
                        <h3 class="font-semibold text-foreground mb-3">Description</h3>
                        <div class="prose prose-sm text-muted-foreground leading-relaxed">
                            {{ product.description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Related Products -->
            <div v-if="relatedProducts.length" class="mt-16">
                <h2 class="text-2xl font-bold text-foreground mb-8">You may also like</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <div v-for="product in relatedProducts" :key="product.id" class="group">
                        <Link :href="`/product/${product.slug}`" class="block">
                            <div class="aspect-square bg-card rounded-2xl overflow-hidden mb-4 relative border border-border">
                                <img v-if="product.images?.length" :src="product.images[0]" :alt="product.name"
                                    class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                                <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/40">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <!-- Sale Badge -->
                                <span v-if="product.is_on_sale"
                                    class="absolute top-3 left-3 bg-destructive text-destructive-foreground text-xs font-bold px-2 py-1 rounded-full">
                                    -{{ product.discount_percentage }}%
                                </span>
                            </div>
                        </Link>
                        <div class="px-1">
                            <Link :href="`/product/${product.slug}`">
                                <h3
                                    class="font-semibold text-foreground mb-1 line-clamp-2 group-hover:text-accent-foreground transition-colors">
                                    {{ product.name }}
                                </h3>
                            </Link>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-foreground">
                                    {{ formatPrice(product.price) }}
                                </span>
                                <span v-if="product.compare_price" class="text-sm text-muted-foreground line-through">
                                    {{ formatPrice(product.compare_price) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
