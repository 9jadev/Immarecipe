<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Pencil, Trash2, ToggleLeft, ToggleRight, Plus } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import type { BreadcrumbItem } from '@/types';

interface DispatchLocation {
    id: number;
    name: string;
    price: number;
    is_active: boolean;
    sort_order: number;
    created_at: string;
}

defineProps<{
    dispatchLocations: DispatchLocation[];
}>();

defineOptions({
    layout: AdminLayout,
});

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Dispatch Locations', href: '/admin/dispatch-locations' },
];

const formatPrice = (price: number) => {
    return new Intl.NumberFormat('en-NG', {
        style: 'currency',
        currency: 'NGN',
        minimumFractionDigits: 0,
    }).format(price);
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const deleteDispatchLocation = (location: DispatchLocation) => {
    if (confirm('Are you sure you want to delete this dispatch location?')) {
        router.delete(`/admin/dispatch-locations/${location.id}`);
    }
};

const toggleActive = (location: DispatchLocation) => {
    router.post(`/admin/dispatch-locations/${location.id}/toggle-active`);
};
</script>

<template>
    <Head title="Dispatch Locations" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <MapPin class="h-8 w-8" />
                    Dispatch Locations
                </h1>
                <p class="text-muted-foreground">Manage dispatch locations and prices.</p>
            </div>
            <Link href="/admin/dispatch-locations/create">
                <Button>
                    <Plus class="h-4 w-4 mr-2" />
                    Add Location
                </Button>
            </Link>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>All Dispatch Locations</CardTitle>
                <CardDescription>A list of all dispatch locations and prices.</CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="dispatchLocations.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No dispatch locations found.</p>
                    <Link href="/admin/dispatch-locations/create" class="mt-4 inline-block">
                        <Button variant="outline">
                            <Plus class="h-4 w-4 mr-2" />
                            Create your first location
                        </Button>
                    </Link>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">Name</th>
                                <th class="text-left py-3 px-2 font-medium">Price</th>
                                <th class="text-left py-3 px-2 font-medium">Status</th>
                                <th class="text-left py-3 px-2 font-medium">Sort Order</th>
                                <th class="text-left py-3 px-2 font-medium">Created</th>
                                <th class="text-right py-3 px-2 font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="location in dispatchLocations" :key="location.id"
                                class="border-b border-border/50">
                                <td class="py-3 px-2">
                                    <p class="font-medium">{{ location.name }}</p>
                                </td>
                                <td class="py-3 px-2">
                                    <span class="font-medium">{{ formatPrice(location.price) }}</span>
                                </td>
                                <td class="py-3 px-2">
                                    <Badge :variant="location.is_active ? 'default' : 'secondary'">
                                        {{ location.is_active ? 'Active' : 'Inactive' }}
                                    </Badge>
                                </td>
                                <td class="py-3 px-2">{{ location.sort_order }}</td>
                                <td class="py-3 px-2 text-muted-foreground">{{ formatDate(location.created_at) }}</td>
                                <td class="py-3 px-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <Button variant="ghost" size="sm" @click="toggleActive(location)"
                                            :title="location.is_active ? 'Deactivate' : 'Activate'">
                                            <ToggleRight v-if="location.is_active" class="h-4 w-4 text-green-500" />
                                            <ToggleLeft v-else class="h-4 w-4 text-muted-foreground" />
                                        </Button>
                                        <Link :href="`/admin/dispatch-locations/${location.id}/edit`">
                                            <Button variant="ghost" size="sm">
                                                <Pencil class="h-4 w-4" />
                                            </Button>
                                        </Link>
                                        <Button variant="ghost" size="sm" @click="deleteDispatchLocation(location)"
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
