<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Package, Pencil, Trash2, ToggleLeft, ToggleRight, Plus, Star, Search, Filter } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { ref, watch } from 'vue';

interface Category {
    id: number;
    name: string;
}

interface Product {
    id: number;
    name: string;
    slug: string;
    sku: string;
    price: number;
    compare_price: number | null;
    stock_count: number;
    stock_status: string;
    is_active: boolean;
    is_featured: boolean;
    category: Category | null;
    created_at: string;
}

interface PaginatedProducts {
    data: Product[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    products: PaginatedProducts;
    categories: Category[];
    filters: {
        search?: string;
        category?: string;
        status?: string;
        stock_status?: string;
    };
}>();

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search ?? '');
const categoryFilter = ref(props.filters.category ?? '');
const statusFilter = ref(props.filters.status ?? '');
const stockStatusFilter = ref(props.filters.stock_status ?? '');

watch([search, categoryFilter, statusFilter, stockStatusFilter], () => {
    router.get('/admin/products', {
        search: search.value || undefined,
        category: categoryFilter.value || undefined,
        status: statusFilter.value || undefined,
        stock_status: stockStatusFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    });
}, { deep: true });

const deleteProduct = (product: Product) => {
    if (confirm('Are you sure you want to delete this product?')) {
        router.delete(`/admin/products/${product.id}`);
    }
};

const toggleActive = (product: Product) => {
    router.post(`/admin/products/${product.id}/toggle-active`);
};

const toggleFeatured = (product: Product) => {
    router.post(`/admin/products/${product.id}/toggle-featured`);
};

const formatPrice = (price: number) => {
    return '₦' + Number(price).toFixed(2);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getStockStatusColor = (status: string) => {
    switch (status) {
        case 'in_stock':
            return 'default';
        case 'low_stock':
            return 'secondary';
        case 'out_of_stock':
            return 'destructive';
        default:
            return 'secondary';
    }
};

const getStockStatusLabel = (status: string) => {
    switch (status) {
        case 'in_stock':
            return 'In Stock';
        case 'low_stock':
            return 'Low Stock';
        case 'out_of_stock':
            return 'Out of Stock';
        default:
            return status;
    }
};
</script>

<template>

    <Head title="Products" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <Package class="h-8 w-8" />
                    Products
                </h1>
                <p class="text-muted-foreground">
                    Manage your product inventory.
                </p>
            </div>
            <Button as-child>
                <Link href="/admin/products/create">
                    <Plus class="h-4 w-4 mr-2" />
                    Add Product
                </Link>
            </Button>
        </div>

        <!-- Filters -->
        <Card>
            <CardHeader class="pb-4">
                <CardTitle class="text-base flex items-center gap-2">
                    <Filter class="h-4 w-4" />
                    Filters
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search products..." class="pl-9" />
                    </div>
                    <Select v-model="categoryFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Categories" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Categories</SelectItem>
                            <SelectItem v-for="cat in categories" :key="cat.id" :value="String(cat.id)">
                                {{ cat.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="statusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="inactive">Inactive</SelectItem>
                            <SelectItem value="featured">Featured</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select v-model="stockStatusFilter">
                        <SelectTrigger>
                            <SelectValue placeholder="All Stock Status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">All Stock Status</SelectItem>
                            <SelectItem value="in_stock">In Stock</SelectItem>
                            <SelectItem value="low_stock">Low Stock</SelectItem>
                            <SelectItem value="out_of_stock">Out of Stock</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </CardContent>
        </Card>

        <!-- Products Table -->
        <Card>
            <CardHeader>
                <CardTitle>All Products</CardTitle>
                <CardDescription>
                    A total of {{ products.total }} products.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="products.data.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No products found.</p>
                    <Button variant="outline" as-child class="mt-4">
                        <Link href="/admin/products/create">
                            <Plus class="h-4 w-4 mr-2" />
                            Create your first product
                        </Link>
                    </Button>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Product</th>
                                <th class="text-left py-3 px-2 font-medium">SKU</th>
                                <th class="text-left py-3 px-2 font-medium">Category</th>
                                <th class="text-left py-3 px-2 font-medium">Price</th>
                                <th class="text-left py-3 px-2 font-medium">Stock</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-right py-3 px-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="product in products.data" :key="product.id" class="border-b border-border/50">
                                <td class="py-3 px-2">
                                    <div class="flex items-center gap-2">
                                        <div>
                                            <p class="font-medium">{{ product.name }}</p>
                                            <p class="text-xs text-muted-foreground">{{ product.slug }}</p>
                                        </div>
                                        <Star v-if="product.is_featured" class="h-4 w-4 text-yellow-500 fill-yellow-500" />
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <code class="bg-muted px-2 py-1 rounded text-xs">
                                        {{ product.sku }}
                                    </code>
                                </td>
                                <td class="py-3 px-2">
                                    <span v-if="product.category">{{ product.category.name }}</span>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td class="py-3 px-2">
                                    <div>
                                        <span class="font-medium">{{ formatPrice(product.price) }}</span>
                                        <span v-if="product.compare_price" class="text-xs text-muted-foreground line-through ml-1">
                                            {{ formatPrice(product.compare_price) }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center gap-2">
                                        <span>{{ product.stock_count }}</span>
                                        <Badge :variant="getStockStatusColor(product.stock_status)">
                                            {{ getStockStatusLabel(product.stock_status) }}
                                        </Badge>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <Badge :variant="product.is_active ? 'default' : 'secondary'">
                                        {{ product.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center justify-end gap-1">
                                        <Button variant="ghost" size="sm" @click="toggleFeatured(product)"
                                            :title="product.is_featured ? 'Remove from featured' : 'Add to featured'">
                                            <Star class="h-4 w-4" :class="product.is_featured ? 'text-yellow-500 fill-yellow-500' : ''" />
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="toggleActive(product)"
                                            :title="product.is_active ? 'Deactivate' : 'Activate'">
                                            <ToggleRight v-if="product.is_active" class="h-4 w-4 text-green-500" />
                                            <ToggleLeft v-else class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                        <Button variant="ghost" size="sm" as-child>
                                            <Link :href="`/admin/products/${product.id}/edit`">
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" @click="deleteProduct(product)"
                                            class="text-destructive hover:text-destructive">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <div v-if="products.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ products.from }} to {{ products.to }} of {{ products.total }} products
                        </p>
                        <div class="flex gap-1">
                            <Link v-for="link in products.links" :key="link.label"
                                :href="link.url || '#'" :only="['products']">
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
