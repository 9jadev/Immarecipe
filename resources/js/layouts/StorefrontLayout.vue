<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppShell from '@/components/AppShell.vue';
import AppContent from '@/components/AppContent.vue';
import { useCart } from '@/composables/useCart';

const page = usePage();
const { cartCount, initializeCart } = useCart();

const searchQuery = ref('');
const isMenuOpen = ref(false);

const auth = computed(() => page.props.auth);
const flash = computed(() => page.props.flash as { success?: string; error?: string });

const search = () => {
    if (searchQuery.value.trim()) {
        window.location.href = `/?search=${encodeURIComponent(searchQuery.value)}`;
    }
};

onMounted(() => {
    initializeCart();
    // Show flash messages
    if (flash.value?.success) {
        console.log(flash.value.success);
    }
    if (flash.value?.error) {
        console.error(flash.value.error);
    }
});
</script>

<template>
    <AppShell variant="header" class="dark bg-background text-foreground">
        <!-- Header -->
        <header class="sticky top-0 z-50 w-full bg-secondary text-foreground shadow-sm">
            <!-- Promo Banner -->
            <div class="bg-primary text-primary-foreground text-sm py-2.5 px-4 text-center font-medium">
                <span>🎉 Highly Discounted Fee on all Website Orders!</span>
            </div>

            <!-- Main Header -->
            <div class="container mx-auto px-4 py-4">
                <div class="flex items-center justify-between gap-4">
                    <!-- Logo -->
                    <Link href="/" class="flex items-center gap-2 flex-shrink-0">
                        <div class="flex items-center gap-1">
                            <img src="/logo1.png" alt="Immarecipe" class="h-10 w-auto object-contain" />
                        </div>
                    </Link>

                    <!-- Search Bar (Desktop) -->
                    <div class="hidden md:flex flex-1 max-w-xl">
                        <form @submit.prevent="search" class="w-full">
                            <div class="relative flex items-center">
                                <div class="absolute left-4 z-10 pointer-events-none">
                                    <svg class="w-5 h-5 text-accent-foreground/80 transition-colors duration-200"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input v-model="searchQuery" type="text"
                                    placeholder="Search for products, categories..."
                                    class="w-full pl-12 pr-5 py-3 bg-card border border-border rounded-full text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-primary/40 focus:border-primary/40 outline-none transition-all duration-300 hover:bg-accent" />
                            </div>
                        </form>
                    </div>

                    <!-- Right Actions -->
                    <div class="flex items-center gap-4">
                        <!-- Currency Selector -->
                        <div
                            class="hidden sm:flex items-center gap-2 px-3 py-1.5 bg-card rounded-full text-sm text-muted-foreground border border-border">
                            <span>🇳🇬</span>
                            <span class="font-medium">NGN</span>
                        </div>

                        <!-- Auth -->
                        <div class="hidden sm:flex items-center gap-3">
                            <!-- Logged in user dropdown -->
                            <div v-if="auth?.user" class="relative group">
                                <button
                                    class="flex items-center gap-2 px-3 py-2 hover:bg-accent rounded-full transition-colors">
                                    <div
                                        class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-primary-foreground text-sm font-medium">
                                        {{ auth.user.name?.charAt(0).toUpperCase() || 'U' }}
                                    </div>
                                    <span class="text-sm text-foreground font-medium">{{ auth.user.name?.split(' ')[0]
                                    }}</span>
                                    <svg class="w-4 h-4 text-muted-foreground" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
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
                            <!-- Guest links -->
                            <template v-else>
                                <Link :href="'/login'"
                                    class="text-sm text-foreground hover:text-accent-foreground font-medium transition-colors">
                                    Login
                                </Link>
                                <Link :href="'/register'"
                                    class="text-sm px-4 py-2 bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground rounded-full font-medium transition-colors">
                                    Sign Up
                                </Link>
                            </template>
                        </div>

                        <!-- Cart -->
                        <Link href="/cart"
                            class="relative flex items-center gap-2 px-4 py-2 bg-card hover:bg-accent rounded-full transition-colors border border-border">
                            <svg class="w-5 h-5 text-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <span v-if="cartCount > 0" class="text-sm font-semibold text-foreground">{{ cartCount
                            }}</span>
                            <span v-else class="text-sm font-medium text-muted-foreground">Cart</span>
                        </Link>

                        <!-- Mobile Menu Button -->
                        <button @click="isMenuOpen = !isMenuOpen"
                            class="md:hidden p-2 hover:bg-accent rounded-lg transition-colors">
                            <svg v-if="!isMenuOpen" class="w-6 h-6 text-foreground" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                            <svg v-else class="w-6 h-6 text-foreground" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Mobile Search -->
                <div class="md:hidden mt-4">
                    <form @submit.prevent="search">
                        <div class="relative flex items-center">
                            <div class="absolute left-4 z-10 pointer-events-none">
                                <svg class="w-5 h-5 text-accent-foreground/80 transition-colors duration-200"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input v-model="searchQuery" type="text" placeholder="Search for products, categories..."
                                class="w-full pl-12 pr-5 py-3 bg-card border border-border rounded-full text-foreground placeholder:text-muted-foreground focus:ring-2 focus:ring-primary/40 focus:border-primary/40 outline-none transition-all duration-300" />
                        </div>
                    </form>
                </div>

                <!-- Mobile Menu -->
                <div v-if="isMenuOpen" class="md:hidden mt-4 pt-4 border-t border-border">
                    <nav class="flex flex-col gap-1">
                        <Link href="/" class="py-2.5 px-3 text-foreground hover:bg-accent rounded-lg font-medium">Home
                        </Link>
                        <!-- Logged in user mobile menu -->
                        <template v-if="auth?.user">
                            <div class="py-2.5 px-3 flex items-center gap-3">
                                <div
                                    class="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-primary-foreground text-sm font-medium">
                                    {{ auth.user.name?.charAt(0).toUpperCase() || 'U' }}
                                </div>
                                <span class="text-foreground font-medium">{{ auth.user.name }}</span>
                            </div>
                            <Link href="/settings/profile"
                                class="py-2.5 px-3 pl-12 text-foreground hover:bg-accent rounded-lg">
                                My Profile
                            </Link>
                            <Link href="/settings/password"
                                class="py-2.5 px-3 pl-12 text-foreground hover:bg-accent rounded-lg">
                                Change Password
                            </Link>
                            <Link href="/logout" method="post" as="button"
                                class="py-2.5 px-3 pl-12 text-destructive hover:bg-accent rounded-lg text-left">
                                Logout
                            </Link>
                        </template>
                        <!-- Guest mobile menu -->
                        <template v-else>
                            <Link :href="'/login'"
                                class="py-2.5 px-3 text-foreground hover:bg-accent rounded-lg font-medium">
                                Login
                            </Link>
                            <Link :href="'/register'"
                                class="py-2.5 px-3 bg-primary text-primary-foreground hover:bg-[var(--primary-hover)] rounded-lg font-medium text-center">
                                Sign Up
                            </Link>
                        </template>
                    </nav>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <AppContent variant="header" class="min-h-screen bg-background text-foreground">
            <slot />
        </AppContent>

        <!-- Footer -->
        <footer class="bg-secondary text-foreground mt-auto border-t border-border">
            <div class="container mx-auto px-4 py-12">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <!-- About -->
                    <div>
                        <img src="/logo1.png" alt="Immarecipe" class="mb-4 h-12 w-auto object-contain" />
                        <p class="text-muted-foreground text-sm leading-relaxed">
                            Immarecipe parfait and treats in Abia State, Nigeria. Fresh, delicious, and
                            delivered to your doorstep.
                        </p>
                    </div>

                    <!-- Quick Links -->
                    <div>
                        <h4 class="font-semibold text-foreground mb-4">Quick Links</h4>
                        <nav class="flex flex-col gap-2 text-sm">
                            <Link href="/" class="text-muted-foreground hover:text-accent-foreground transition-colors">
                                Home
                            </Link>
                            <Link href="/cart"
                                class="text-muted-foreground hover:text-accent-foreground transition-colors">Cart
                            </Link>
                            <Link href="/spin"
                                class="text-muted-foreground hover:text-accent-foreground transition-colors">Spin
                                & Win
                            </Link>
                        </nav>
                    </div>

                    <!-- Contact -->
                    <div>
                        <h4 class="font-semibold text-foreground mb-4">Contact Us</h4>
                        <div class="text-sm space-y-3">
                            <p class="flex items-center gap-2 text-muted-foreground">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                                +2347032887276
                            </p>
                            <p class="flex items-center gap-2 text-muted-foreground">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                                Umuahia, Abia, Nigeria
                            </p>
                        </div>
                    </div>

                    <!-- Newsletter -->
                    <div>
                        <h4 class="font-semibold text-foreground mb-4">Newsletter</h4>
                        <p class="text-sm text-muted-foreground mb-3">Get updates on new products and discounts</p>
                        <form class="flex gap-2">
                            <Input type="text" placeholder="Your email"
                                class="flex-1 bg-card border-border text-foreground placeholder:text-muted-foreground" />
                            <Button
                                class="bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground shrink-0">Subscribe</Button>
                        </form>
                    </div>
                </div>

                <!-- Bottom Bar -->
                <div class="border-t border-border mt-10 pt-8 text-center text-sm text-muted-foreground">
                    <p>&copy; {{ new Date().getFullYear() }} Immarecipe. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- WhatsApp Floating Button -->
        <a href="https://wa.me/2347032887276" target="blank" rel="noopener noreferrer"
            class="fixed bottom-6 right-6 bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground p-4 rounded-full shadow-lg hover:shadow-xl z-50 transition-all hover:scale-105">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z" />
            </svg>
        </a>
    </AppShell>
</template>
