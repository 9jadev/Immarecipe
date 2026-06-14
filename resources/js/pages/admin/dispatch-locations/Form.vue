<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { MapPin, ArrowLeft, Save } from 'lucide-vue-next';
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

interface DispatchLocation {
    id: number;
    name: string;
    price: number;
    is_active: boolean;
    sort_order: number;
}

const props = defineProps<{
    dispatchLocation?: DispatchLocation;
}>();

defineOptions({
    layout: AdminLayout,
});

const isEditing = !!props.dispatchLocation;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Dispatch Locations', href: '/admin/dispatch-locations' },
    {
        title: isEditing ? 'Edit Dispatch Location' : 'Create Dispatch Location',
        href: isEditing ? `/admin/dispatch-locations/${props.dispatchLocation?.id}/edit` : '/admin/dispatch-locations/create',
    },
];

const form = useForm({
    name: props.dispatchLocation?.name ?? '',
    price: props.dispatchLocation?.price ?? 0,
    is_active: props.dispatchLocation?.is_active ?? true,
    sort_order: props.dispatchLocation?.sort_order ?? 0,
});

const submit = () => {
    if (isEditing && props.dispatchLocation) {
        form.put(`/admin/dispatch-locations/${props.dispatchLocation.id}`);
        return;
    }

    form.post('/admin/dispatch-locations');
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Dispatch Location' : 'Create Dispatch Location'" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <MapPin class="h-8 w-8" />
                    {{ isEditing ? 'Edit Dispatch Location' : 'Create Dispatch Location' }}
                </h1>
                <p class="text-muted-foreground">
                    {{ isEditing ? 'Update dispatch location details.' : 'Add a new dispatch location and price.' }}
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link href="/admin/dispatch-locations">
                    <ArrowLeft class="h-4 w-4 mr-2" />
                    Back
                </Link>
            </Button>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                    <CardDescription>Location name, price, and status.</CardDescription>
                </CardHeader>
                <CardContent class="space-y-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="name">Name</Label>
                            <Input id="name" v-model="form.name" placeholder="e.g. Afara" />
                            <p v-if="form.errors.name" class="text-sm text-destructive">{{ form.errors.name }}</p>
                        </div>

                        <div class="space-y-2">
                            <Label for="price">Price (₦)</Label>
                            <Input id="price" v-model="form.price" type="number" min="0" step="0.01" />
                            <p v-if="form.errors.price" class="text-sm text-destructive">{{ form.errors.price }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-2">
                            <Label for="sort_order">Sort Order</Label>
                            <Input id="sort_order" v-model="form.sort_order" type="number" min="0" step="1" />
                            <p v-if="form.errors.sort_order" class="text-sm text-destructive">{{ form.errors.sort_order }}</p>
                        </div>

                        <div class="flex items-center space-x-2 pt-7">
                            <Checkbox id="is_active" :checked="form.is_active"
                                @update:checked="form.is_active = $event" />
                            <Label for="is_active" class="cursor-pointer">Active</Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <Button type="submit" class="w-full" :disabled="form.processing">
                        <Save class="h-4 w-4 mr-2" />
                        {{ isEditing ? 'Update Location' : 'Create Location' }}
                    </Button>
                </CardContent>
            </Card>
        </form>
    </div>
</template>
