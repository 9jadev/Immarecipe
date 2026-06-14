<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/StorefrontLayout.vue';
import { useCart } from '@/composables/useCart';

interface Category {
    id: number;
    name: string;
    slug: string;
}

interface VariantItem {
    id: number;
    sku: string;
    variant_name: string;
    price: number | null;
    compare_price: number | null;
    stock_count: number;
    is_active: boolean;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    price: number;
    compare_price: number | null;
    images: string[] | null;
    category: Category | null;
    is_on_sale: boolean;
    discount_percentage: number | null;
    has_variants: boolean;
    variants: VariantItem[];
}

interface Props {
    products: {
        data: Product[];
        links: { url: string | null; label: string; active: boolean }[];
        current_page: number;
        last_page: number;
    };
    categories: Category[];
    currentCategory?: Category;
    filters: {
        category?: string;
        search?: string;
    };
}

const props = defineProps<Props>();
const { addToCart, isLoading } = useCart();

const searchQuery = ref(props.filters.search || '');
const selectedCategory = ref(props.filters.category || '');

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(price);
};

const handleAddToCart = async (product: Product) => {
    // If product has variants, redirect to product page to select variant
    if (product.has_variants) {
        router.visit(`/product/${product.slug}`);
        return;
    }

    try {
        await addToCart(product.id);
    } catch (e) {
        console.error('Failed to add to cart', e);
    }
};

const filterByCategory = (slug?: string) => {
    selectedCategory.value = slug || '';
    router.get('/', { category: slug || undefined, search: searchQuery.value || undefined }, { preserveState: true });
};

const search = () => {
    router.get('/', { search: searchQuery.value || undefined, category: selectedCategory.value || undefined }, { preserveState: true });
};

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = '';
    router.get('/', {});
};
</script>

<template>

    <Head title="Home - Products" />
    <AppLayout>
        <!-- Hero Section -->
        <div class="bg-secondary py-16 px-4 border-b border-border">
            <div class="max-w-7xl mx-auto text-center">
                <h1 class="text-4xl md:text-5xl font-bold text-foreground mb-4">
                    <span class="text-accent-foreground">Immarecipe</span>
                </h1>
                <p class="text-lg text-muted-foreground mb-8">Taste of Freshness - Delivered to your doorstep</p>
            </div>
        </div>

        <!-- Categories -->
        <div class="bg-background shadow-sm sticky top-[73px] z-10 border-b border-border">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex gap-2 py-4 overflow-x-auto scrollbar-hide">
                    <button @click="filterByCategory()" :class="[
                        'px-5 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap',
                        !selectedCategory
                            ? 'bg-primary text-primary-foreground shadow-md'
                            : 'bg-secondary text-secondary-foreground hover:bg-accent hover:text-accent-foreground'
                    ]">
                        All
                    </button>
                    <button v-for="category in categories" :key="category.id" @click="filterByCategory(category.slug)"
                        :class="[
                            'px-5 py-2 rounded-full text-sm font-medium transition-all whitespace-nowrap',
                            selectedCategory === category.slug
                                ? 'bg-primary text-primary-foreground shadow-md'
                                : 'bg-secondary text-secondary-foreground hover:bg-accent hover:text-accent-foreground'
                        ]">
                        {{ category.name }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Products Grid -->
        <div class="max-w-7xl mx-auto px-4 py-10">
            <!-- Active Filters -->
            <div v-if="selectedCategory || searchQuery" class="flex items-center gap-3 mb-6">
                <span class="text-sm text-muted-foreground">Active filters:</span>
                <span v-if="selectedCategory"
                    class="inline-flex items-center gap-1 px-3 py-1 bg-secondary text-secondary-foreground rounded-full text-sm">
                    {{categories.find(c => c.slug === selectedCategory)?.name}}
                    <button @click="filterByCategory()" class="hover:text-accent-foreground">&times;</button>
                </span>
                <span v-if="searchQuery"
                    class="inline-flex items-center gap-1 px-3 py-1 bg-secondary text-secondary-foreground rounded-full text-sm">
                    "{{ searchQuery }}"
                    <button @click="searchQuery = ''; search()" class="hover:text-accent-foreground">&times;</button>
                </span>
                <button @click="clearFilters"
                    class="text-sm text-muted-foreground hover:text-foreground underline">Clear
                    all</button>
            </div>

            <!-- Products -->
            <div v-if="products.data.length"
                class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div v-for="product in products.data" :key="product.id" class="group">
                    <Link :href="`/product/${product.slug}`" class="block">
                        <div
                            class="relative aspect-square bg-card rounded-2xl overflow-hidden mb-4 border border-border">
                            <img v-if="product.images?.length" :src="product.images[0]" :alt="product.name"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
                            <div v-else class="w-full h-full flex items-center justify-center text-muted-foreground/40">
                                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <p v-if="product.category" class="text-xs text-muted-foreground mb-2">
                            {{ product.category.name }}
                        </p>
                        <div class="flex items-center gap-2 mb-3">
                            <span class="text-lg font-bold text-foreground">
                                {{ formatPrice(product.price) }}
                            </span>
                            <span v-if="product.compare_price" class="text-sm text-muted-foreground line-through">
                                {{ formatPrice(product.compare_price) }}
                            </span>
                        </div>
                        <Button class="w-full rounded-full shadow-sm hover:shadow-md transition-all"
                            @click="handleAddToCart(product)" :disabled="isLoading">
                            {{ product.has_variants ? 'Select Options' : 'Add to Cart' }}
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-20">
                <div class="w-24 h-24 mx-auto mb-6 bg-secondary rounded-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-muted-foreground/40" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-foreground mb-2">No products found</h3>
                <p class="text-muted-foreground mb-6">Try adjusting your search or filter criteria</p>
                <Button @click="clearFilters" class="rounded-full px-8">
                    Clear Filters
                </Button>
            </div>

            <!-- Pagination -->
            <div v-if="products.last_page > 1" class="flex justify-center items-center gap-2 mt-10">
                <Link v-for="link in products.links" :key="link.label" :href="link.url || '#'" :class="[
                    'px-4 py-2 rounded-full text-sm font-medium transition-all',
                    link.active
                        ? 'bg-primary text-primary-foreground shadow-md'
                        : 'bg-card text-foreground hover:bg-secondary border border-border',
                    !link.url && 'opacity-50 cursor-not-allowed pointer-events-none'
                ]" v-html="link.label" />
            </div>
        </div>
    </AppLayout>
</template>
