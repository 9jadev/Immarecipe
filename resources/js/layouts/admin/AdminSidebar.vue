<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    LayoutGrid,
    Users,
    Settings,
    Shield,
    LogOut,
    TicketPercent,
    FolderPlus,
    Package,
    MapPin,
    CreditCard,
    ReceiptText,
} from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import AdminNavUser from './AdminNavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: '/admin/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Products',
        href: '/admin/products',
        icon: Package,
    },
    {
        title: 'Categories',
        href: '/admin/categories',
        icon: FolderPlus,
    },
    {
        title: 'Dispatch Locations',
        href: '/admin/dispatch-locations',
        icon: MapPin,
    },
    {
        title: 'Payments',
        href: '/admin/payments',
        icon: CreditCard,
    },
    {
        title: 'Discount Spinner',
        href: '/admin/discount-spinner',
        icon: TicketPercent,
    },
    {
        title: 'Orders',
        href: '/admin/orders',
        icon: ReceiptText,
    },
    {
        title: 'Users',
        href: '/admin/users',
        icon: Users,
    },
    // {
    //     title: 'Roles & Permissions',
    //     href: '/admin/roles',
    //     icon: Shield,
    // },
    // {
    //     title: 'Settings',
    //     href: '/admin/settings',
    //     icon: Settings,
    // },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Back to Site',
        href: '/',
        icon: LogOut,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link href="/admin/dashboard">
                            <AppLogo subtitle="Admin Panel" />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <AdminNavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
