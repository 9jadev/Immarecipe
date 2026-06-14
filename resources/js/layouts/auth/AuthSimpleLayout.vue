<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const auth = computed(() => page.props.auth);

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="dark min-h-screen bg-background text-foreground flex flex-col">
        <!-- Promo Banner -->
        <div class="bg-primary text-primary-foreground text-sm py-2.5 px-4 text-center font-medium">
            <span>🎉 Highly Discounted Fee on all Website Orders!</span>
        </div>

        <!-- Header -->
        <header class="bg-secondary border-b border-border">
            <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
                <!-- Logo -->
                <Link :href="home()" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center">
                        <AppLogoIcon class="size-5 text-primary-foreground" />
                    </div>
                    <span class="text-lg font-bold text-foreground">Immarecipe</span>
                </Link>

                <!-- Right Side -->
                <div class="flex items-center gap-4">
                    <!-- Currency -->
                    <div
                        class="hidden sm:flex items-center gap-1 text-sm text-muted-foreground bg-card border border-border rounded-full px-3 py-1.5">
                        <span>🇳🇬</span>
                        <span>NGN</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <!-- User Profile (when logged in) -->
                    <div v-if="auth?.user" class="relative group">
                        <button
                            class="flex items-center gap-2 px-3 py-2 hover:bg-accent rounded-full transition-colors">
                            <div
                                class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-primary-foreground text-sm font-medium">
                                {{ auth.user.name?.charAt(0).toUpperCase() || 'U' }}
                            </div>
                            <span class="hidden sm:block text-sm text-foreground font-medium">{{ auth.user.name?.split(' ')[0] }}</span>
                        </button>
                        <!-- Dropdown -->
                        <div
                            class="absolute right-0 top-full mt-2 w-48 bg-card rounded-xl shadow-lg border border-border opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            <div class="py-2">
                                <Link href="/settings/profile"
                                    class="block px-4 py-2 text-sm text-foreground hover:bg-secondary hover:text-accent-foreground">
                                    My Profile
                                </Link>
                                <Link href="/settings/password"
                                    class="block px-4 py-2 text-sm text-foreground hover:bg-secondary hover:text-accent-foreground">
                                    Change Password
                                </Link>
                                <div class="border-t border-border my-1"></div>
                                <Link href="/logout" method="post" as="button"
                                    class="block w-full text-left px-4 py-2 text-sm text-destructive hover:bg-secondary">
                                    Logout
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Cart -->
                    <Link href="/cart" class="relative">
                        <svg class="w-6 h-6 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        <span
                            class="absolute -top-1 -right-1 w-4 h-4 bg-primary text-primary-foreground text-xs rounded-full flex items-center justify-center">0</span>
                    </Link>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center p-6 py-12">
            <div class="w-full max-w-md">
                <!-- Custom Auth Header -->
                <div class="text-center mb-8 bg-card border border-border rounded-2xl p-8 shadow-sm">
                    <div class="inline-flex items-center justify-center gap-2 mb-4">
                        <span class="text-2xl font-bold text-foreground">LOGIN</span>
                        <span class="text-muted-foreground">/</span>
                        <span class="text-2xl font-bold text-foreground">REGISTER</span>
                    </div>
                    <p class="text-muted-foreground text-sm">Sign in or create an account to continue.</p>
                </div>

                <!-- Form -->
                <slot />
            </div>
        </div>
    </div>
</template>
