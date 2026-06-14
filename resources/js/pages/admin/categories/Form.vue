<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { FolderPlus, ArrowLeft, Save } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface Category {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    is_active: boolean;
    sort_order: number;
}

const props = defineProps<{
    category?: Category;
}>();

defineOptions({
    layout: AdminLayout,
});

const isEditing = !!props.category;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
    },
    {
        title: 'Categories',
        href: '/admin/categories',
    },
    {
        title: isEditing ? 'Edit Category' : 'Create Category',
        href: isEditing ? `/admin/categories/${props.category?.id}/edit` : '/admin/categories/create',
    },
];

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    description: props.category?.description ?? '',
    is_active: props.category?.is_active ?? true,
    sort_order: props.category?.sort_order ?? 0,
});

const submit = () => {
    if (isEditing) {
        form.put(`/admin/categories/${props.category!.id}`);
    } else {
        form.post('/admin/categories');
    }
};
</script>

<template>

    <Head :title="isEditing ? 'Edit Category' : 'Create Category'" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div class="flex items-center gap-4">
            <Link href="/admin/categories">
            <Button variant="ghost" size="sm">
                <ArrowLeft class="h-4 w-4 mr-2" />
                Back
            </Button>
            </Link>
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <FolderPlus class="h-8 w-8" />
                    {{ isEditing ? 'Edit Category' : 'Create Category' }}
                </h1>
                <p class="text-muted-foreground">
                    {{ isEditing ? 'Update the category details.' : 'Add a new category to your store.' }}
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <Card class="max-w-2xl">
            <CardHeader>
                <CardTitle>{{ isEditing ? 'Edit' : 'New' }} Category</CardTitle>
                <CardDescription>
                    Fill in the details below to {{ isEditing ? 'update the' : 'create a new' }} category.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Name -->
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="Enter category name"
                            :class="{ 'border-destructive': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-sm text-destructive">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <!-- Slug -->
                    <div class="grid gap-2">
                        <Label for="slug">Slug (optional)</Label>
                        <Input id="slug" v-model="form.slug" type="text" placeholder="category-slug"
                            :class="{ 'border-destructive': form.errors.slug }" />
                        <p class="text-xs text-muted-foreground">
                            Leave empty to auto-generate from the name.
                        </p>
                        <p v-if="form.errors.slug" class="text-sm text-destructive">
                            {{ form.errors.slug }}
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="grid gap-2">
                        <Label for="description">Description (optional)</Label>
                        <textarea id="description" v-model="form.description"
                            class="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                            placeholder="Enter category description"></textarea>
                        <p v-if="form.errors.description" class="text-sm text-destructive">
                            {{ form.errors.description }}
                        </p>
                    </div>

                    <!-- Sort Order -->
                    <div class="grid gap-2">
                        <Label for="sort_order">Sort Order</Label>
                        <Input id="sort_order" v-model="form.sort_order" type="number" min="0"
                            :class="{ 'border-destructive': form.errors.sort_order }" />
                        <p class="text-xs text-muted-foreground">
                            Categories with lower numbers appear first.
                        </p>
                        <p v-if="form.errors.sort_order" class="text-sm text-destructive">
                            {{ form.errors.sort_order }}
                        </p>
                    </div>

                    <!-- Is Active -->
                    <div class="flex items-center space-x-2">
                        <Checkbox id="is_active" :checked="form.is_active"
                            @update:checked="form.is_active = $event" />
                        <Label for="is_active" class="cursor-pointer">
                            Active
                        </Label>
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="h-4 w-4 mr-2" />
                            {{ isEditing ? 'Update Category' : 'Create Category' }}
                        </Button>
                        <Link href="/admin/categories">
                        <Button type="button" variant="outline">
                            Cancel
                        </Button>
                        </Link>
                    </div>
                </form>
            </CardContent>
        </Card>
    </div>
</template>
