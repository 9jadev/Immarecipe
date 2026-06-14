<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Users,
    Shield,
    Settings,
    TrendingUp,
    Activity,
} from 'lucide-vue-next';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

const stats = [
    {
        title: 'Total Users',
        value: '1,234',
        description: '+12% from last month',
        icon: Users,
    },
    {
        title: 'Active Sessions',
        value: '42',
        description: 'Currently online',
        icon: Activity,
    },
    {
        title: 'Growth Rate',
        value: '+23%',
        description: 'Compared to last month',
        icon: TrendingUp,
    },
];

const quickLinks = [
    {
        title: 'Manage Users',
        description: 'View and manage user accounts',
        href: '/admin/users',
        icon: Users,
    },
    {
        title: 'Roles & Permissions',
        description: 'Configure access controls',
        href: '/admin/roles',
        icon: Shield,
    },
    {
        title: 'System Settings',
        description: 'Configure application settings',
        href: '/admin/settings',
        icon: Settings,
    },
];
</script>

<template>

    <Head title="Admin Dashboard" />

    <div class="flex flex-col gap-6 p-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold tracking-tight">Dashboard</h1>
            <p class="text-muted-foreground">
                Welcome to your admin dashboard. Here's what's happening today.
            </p>
        </div>

        <!-- Stats Cards -->
        <div class="grid gap-4 md:grid-cols-3">
            <Card v-for="stat in stats" :key="stat.title">
                <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                    <CardTitle class="text-sm font-medium">
                        {{ stat.title }}
                    </CardTitle>
                    <component :is="stat.icon" class="h-4 w-4 text-muted-foreground" />
                </CardHeader>
                <CardContent>
                    <div class="text-2xl font-bold">{{ stat.value }}</div>
                    <p class="text-xs text-muted-foreground">
                        {{ stat.description }}
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Quick Links -->
        <div>
            <h2 class="text-xl font-semibold mb-4">Quick Links</h2>
            <div class="grid gap-4 md:grid-cols-3">
                <Card v-for="link in quickLinks" :key="link.title"
                    class="hover:bg-muted/50 transition-colors cursor-pointer">
                    <a :href="link.href">
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">
                                {{ link.title }}
                            </CardTitle>
                            <component :is="link.icon" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <p class="text-sm text-muted-foreground">
                                {{ link.description }}
                            </p>
                        </CardContent>
                    </a>
                </Card>
            </div>
        </div>

        <!-- Recent Activity -->
        <Card>
            <CardHeader>
                <CardTitle>Recent Activity</CardTitle>
                <CardDescription>
                    Latest actions performed in the admin panel
                </CardDescription>
            </CardHeader>
            <CardContent>
                <div class="space-y-4">
                    <div class="flex items-center gap-4">
                        <div class="h-2 w-2 rounded-full bg-green-500"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">System update completed</p>
                            <p class="text-xs text-muted-foreground">2 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="h-2 w-2 rounded-full bg-blue-500"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">New user registered</p>
                            <p class="text-xs text-muted-foreground">5 hours ago</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="h-2 w-2 rounded-full bg-yellow-500"></div>
                        <div class="flex-1">
                            <p class="text-sm font-medium">Settings changed</p>
                            <p class="text-xs text-muted-foreground">1 day ago</p>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
