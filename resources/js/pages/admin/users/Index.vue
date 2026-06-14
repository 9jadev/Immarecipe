<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Users, Search, Filter } from 'lucide-vue-next';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { computed, ref, watch } from 'vue';

interface UserRow {
    id: number;
    name: string;
    email: string;
    created_at: string;
    orders_count: number;
    paid_orders_count: number;
    total_amount_spent: number | string | null;
}

interface PaginatedUsers {
    data: UserRow[];
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

defineOptions({
    layout: AdminLayout,
});

const search = ref(props.filters.search ?? '');
const startDate = ref(props.filters.start_date ?? '');
const endDate = ref(props.filters.end_date ?? '');

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    if (startDate.value) params.set('start_date', startDate.value);
    if (endDate.value) params.set('end_date', endDate.value);
    const query = params.toString();
    return query ? `/admin/users/export?${query}` : '/admin/users/export';
});

watch([search, startDate, endDate], () => {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    );
}, { deep: true });

const formatMoney = (amount: number | string | null) => {
    const value = amount == null ? 0 : Number(amount);
    return '₦' + (Number.isFinite(value) ? value : 0).toFixed(2);
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
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-6">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold tracking-tight flex items-center gap-2">
                    <Users class="h-8 w-8" />
                    Users
                </h1>
                <p class="text-muted-foreground">View customer accounts and purchase totals.</p>
            </div>
            <Button variant="outline" as-child>
                <a :href="exportUrl">Export to Excel</a>
            </Button>
        </div>

        <Card>
            <CardHeader class="pb-4">
                <CardTitle class="text-base flex items-center gap-2">
                    <Filter class="h-4 w-4" />
                    Filters
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input v-model="search" placeholder="Search by name or email..." class="pl-9" />
                    </div>
                    <Input v-model="startDate" type="date" />
                    <Input v-model="endDate" type="date" />
                </div>
            </CardContent>
        </Card>

        <Card>
            <CardHeader>
                <CardTitle>All Users</CardTitle>
                <CardDescription>
                    A total of {{ users.total }} users.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div v-if="users.data.length === 0" class="text-center py-8">
                    <p class="text-muted-foreground">No users found.</p>
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-border">
                                <th class="text-left py-3 px-2 font-medium">User</th>
                                <th class="text-right py-3 px-2 font-medium">Orders</th>
                                <th class="text-right py-3 px-2 font-medium">Paid Orders</th>
                                <th class="text-right py-3 px-2 font-medium">Total Spent</th>
                                <th class="text-left py-3 px-2 font-medium">Joined</th>
                                <th class="text-right py-3 px-2 font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="user in users.data" :key="user.id" class="border-b border-border/50">
                                <td class="py-3 px-2">
                                    <div>
                                        <p class="font-medium">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </td>
                                <td class="py-3 px-2 text-right tabular-nums">{{ user.orders_count }}</td>
                                <td class="py-3 px-2 text-right tabular-nums">{{ user.paid_orders_count }}</td>
                                <td class="py-3 px-2 text-right tabular-nums font-medium">
                                    {{ formatMoney(user.total_amount_spent) }}
                                </td>
                                <td class="py-3 px-2">{{ formatDate(user.created_at) }}</td>
                                <td class="py-3 px-2 text-right">
                                    <Button variant="outline" size="sm" as-child>
                                        <Link :href="`/admin/users/${user.id}`">View</Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div v-if="users.last_page > 1" class="flex items-center justify-between mt-4 pt-4 border-t">
                        <p class="text-sm text-muted-foreground">
                            Showing {{ users.from }} to {{ users.to }} of {{ users.total }} users
                        </p>
                        <div class="flex gap-1">
                            <Link v-for="link in users.links" :key="link.label" :href="link.url || '#'" :only="['users']">
                                <Button
                                    variant="outline"
                                    size="sm"
                                    :disabled="!link.url"
                                    :class="{ 'bg-primary text-primary-foreground': link.active }"
                                    v-html="link.label.replace('&laquo;', '←').replace('&raquo;', '→')"
                                />
                            </Link>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
