<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderPlus, Pencil, Trash2, ToggleLeft, ToggleRight, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
    created_at: string;
}

const props = defineProps<{
    categories: Category[];
}>();

defineOptions({
    layout: AdminLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Categories',
        href: '/admin/categories',
    },
];

const deleteCategory = (category: Category) => {
    if (confirm('Are you sure you want to delete this category?')) {
        router.delete(`/admin/categories/${category.id}`);
    }
};

const toggleActive = (category: Category) => {
    router.post(`/admin/categories/${category.id}/toggle-active`);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>

    <Head title="Categories" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <FolderPlus class="h-8 w-8" />
                    Categories
                </h1>
                <p class="text-muted-foreground">
                    Manage your product categories.
                </p>
            </div>
            <Link href="/admin/categories/create">
            <Button>
                <Plus class="h-4 w-4 mr-2" />
                Add Category
            </Button>
            </Link>
        </div>

        <!-- Categories Table -->
        <Card>
            <CardHeader>
                <CardTitle>All Categories</CardTitle>
                <CardDescription>
                    A list of all categories in your store.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="categories.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No categories found.</p>
                    <Link href="/admin/categories/create" class="mt-4 inline-block">
                    <Button variant="outline">
                        <Plus class="h-4 w-4 mr-2" />
                        Create your first category
                    </Button>
                    </Link>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Name</th>
                                <th class="text-left py-3 px-2 font-medium">Slug</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-left py-3 px-2 font-medium">Sort Order</th>
                                <th class="text-left py-3 px-2 font-medium">Created</th>
                                <th class="text-right py-3 px-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="category in categories" :key="category.id" class="border-b border-border/50">
                                <td class="py-3 px-2">
                                    <div>
                                        <p class="font-medium">{{ category.name }}</p>
                                        <p v-if="category.description" class="text-xs text-muted-foreground truncate max-w-xs">
                                            {{ category.description }}
                                        </p>
                                    </div>
                                </td>
                                <td class="py-3 px-2">
                                    <code class="bg-muted px-2 py-1 rounded text-xs">
                                        {{ category.slug }}
                                    </code>
                                </td>
                                <td class="py-3 px-2">
                                    <Badge :variant="category.is_active ? 'default' : 'secondary'">
                                        {{ category.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-2">
                                    {{ category.sort_order }}
                                </td>
                                <td class="py-3 px-2 text-muted-foreground">
                                    {{ formatDate(category.created_at) }}
                                </td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="ghost" size="sm" @click="toggleActive(category)"
                                            :title="category.is_active ? 'Deactivate' : 'Activate'">
                                            <ToggleRight v-if="category.is_active" class="h-4 w-4 text-green-500" />
                                            <ToggleLeft v-else class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                        <Link :href="`/admin/categories/${category.id}/edit`">
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" @click="deleteCategory(category)"
                                            class="text-destructive hover:text-destructive">
                                            <Trash2 class="h-4 w-4" />
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
