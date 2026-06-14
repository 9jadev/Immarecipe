<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ChevronsUpDown, LogOut } from 'lucide-vue-next';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const admin = computed(() => page.props.admin?.user);
const { isMobile, state } = useSidebar();
const { getInitials } = useInitials();
</script>

<template>
    <SidebarMenu v-if="admin">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg"
                        class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <div
                            class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-primary-foreground font-semibold">
                            {{ getInitials(admin.name) }}
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-medium">{{ admin.name }}</span>
                            <span class="truncate text-xs text-muted-foreground">{{ admin.email }}</span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-(--reka-dropdown-menu-trigger-width) min-w-56 rounded-lg" :side="isMobile
                        ? 'bottom'
                        : state === 'collapsed'
                            ? 'left'
                            : 'bottom'
                    " align="end" :side-offset="4">
                    <form method="POST" action="/admin/logout">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token">
                        <DropdownMenuItem as-child>
                            <button type="submit" class="w-full cursor-pointer">
                                <LogOut class="mr-2 h-4 w-4" />
                                Log out
                            </button>
                        </DropdownMenuItem>
                    </form>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
