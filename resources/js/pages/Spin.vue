<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import { Copy, Check, Sparkles, Clock, AlertCircle, Gift, RotateCcw } from 'lucide-vue-next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    isActive: boolean;
    message?: string;
    discounts?: Array<{
        percentage: number;
        label: string;
        color: string;
        probability: number;
    }>;
    settings?: {
        maxSpinsPerEmail: number;
        codeExpiryHours: number;
    };
}>();

const page = usePage();

const form = useForm({
    name: '',
    phone: '',
});

const isSpinning = ref(false);
const hasSpun = ref(false);
const wheelRotation = ref(0);
const selectedDiscount = ref<{ percentage: number; label: string; color: string; probability: number } | null>(null);
const showResult = ref(false);
const copied = ref(false);
const pulseAnimation = ref(false);

const result = computed(() => page.props.result as {
    discount: { percentage: number; label: string; color: string; probability: number };
    code: string;
    expiresAt: string;
} | null);

watch(result, (newResult) => {
    if (newResult) {
        selectedDiscount.value = newResult.discount;
        showResult.value = true;
        // Trigger confetti for wins (percentage > 0)
        if (newResult.discount.percentage > 0) {
            setTimeout(triggerConfetti, 300);
        }
    }
}, { immediate: true });

const wheelSegments = computed(() => {
    if (!props.discounts) return [];
    const segmentAngle = 360 / props.discounts.length;
    return props.discounts.map((discount, index) => ({
        ...discount,
        rotation: index * segmentAngle,
    }));
});

const spin = () => {
    if (isSpinning.value) return;

    pulseAnimation.value = false;
    isSpinning.value = true;

    // Calculate final rotation (6-12 full spins + random angle)
    const spins = 6 + Math.random() * 6;
    const finalRotation = wheelRotation.value + spins * 360 + Math.random() * 360;
    wheelRotation.value = finalRotation;

    // Submit form after spin starts
    setTimeout(() => {
        form.post('/spin', {
            onSuccess: () => {
                hasSpun.value = true;
                setTimeout(() => {
                    isSpinning.value = false;
                }, 1000);
            },
            onError: () => {
                isSpinning.value = false;
            }
        });
    }, 3500);
};

const copyCode = () => {
    if (result.value?.code) {
        navigator.clipboard.writeText(result.value.code);
        copied.value = true;
        setTimeout(() => copied.value = false, 2000);
    }
};

const formatExpiry = (expiresAt: string) => {
    const date = new Date(expiresAt);
    return date.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const isWin = computed(() => {
    return selectedDiscount.value && selectedDiscount.value.percentage > 0;
});

const resetSpin = () => {
    hasSpun.value = false;
    showResult.value = false;
    form.reset();
    pulseAnimation.value = true;
};

// Confetti
const confettiParticles = ref<Array<{ id: number; x: number; y: number; color: string; size: number; delay: number; duration: number; shape: string }>>([]);
const showConfetti = ref(false);

const triggerConfetti = () => {
    const colors = ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6', '#f97316', '#22c55e', '#06b6d4'];
    const shapes = ['rounded-sm', 'rounded-full', 'rounded-none'];
    const particles = [];

    // Create 100 particles for a full celebration
    for (let i = 0; i < 100; i++) {
        particles.push({
            id: i,
            x: Math.random() * 100,
            y: -10 - Math.random() * 20,
            color: colors[Math.floor(Math.random() * colors.length)],
            size: 8 + Math.random() * 8,
            delay: Math.random() * 0.5,
            duration: 2.5 + Math.random() * 1.5,
            shape: shapes[Math.floor(Math.random() * shapes.length)],
        });
    }
    confettiParticles.value = particles;
    showConfetti.value = true;
    setTimeout(() => {
        showConfetti.value = false;
        confettiParticles.value = [];
    }, 4000);
};
</script>

<template>

    <Head title="Spin & Win" />

    <div class="min-h-screen bg-background text-foreground flex items-center justify-center p-4 relative overflow-hidden"
        style="font-family: 'Lexend', sans-serif;">
        <!-- Confetti -->
        <div v-if="showConfetti" class="fixed inset-0 pointer-events-none z-[100]">
            <div v-for="p in confettiParticles" :key="p.id" class="absolute rounded-sm" :class="p.shape" :style="{
                left: p.x + '%',
                top: p.y + '%',
                width: p.size + 'px',
                height: p.size + 'px',
                backgroundColor: p.color,
                animationName: 'confetti-celebration',
                animationDuration: p.duration + 's',
                animationDelay: p.delay + 's',
                animationTimingFunction: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                animationFillMode: 'forwards',
                animationIterationCount: '1'
            }" />
        </div>
        <!-- Main Container -->
        <div class="w-full max-w-md">
            <!-- Header -->
            <div class="text-center mb-8">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-card rounded-2xl mb-4 shadow-lg border border-border">
                    <Gift class="w-8 h-8 text-accent-foreground" />
                </div>
                <h1 class="text-3xl font-semibold text-foreground mb-2 tracking-tight">
                    Spin & Win
                </h1>
                <p class="text-muted-foreground text-sm">Enter your number for a chance to win exclusive discounts</p>
            </div>

            <!-- Inactive Message -->
            <Card v-if="!isActive" class="bg-card border-border shadow-lg">
                <CardContent class="p-6 text-center">
                    <AlertCircle class="w-12 h-12 text-muted-foreground mx-auto mb-4" />
                    <p class="text-muted-foreground text-sm">{{ message }}</p>
                </CardContent>
            </Card>

            <template v-else>
                <!-- Spinning Wheel Container -->
                <div class="relative mb-8">
                    <!-- Wheel Container -->
                    <div class="relative w-72 h-72 mx-auto">
                        <!-- Outer Ring -->
                        <div class="absolute inset-0 rounded-full border-2 border-border shadow-xl"></div>

                        <!-- Pointer -->
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 z-20">
                            <div
                                class="w-0 h-0 border-l-[12px] border-l-transparent border-r-[12px] border-r-transparent border-t-[24px] border-t-accent-foreground">
                            </div>
                        </div>

                        <!-- Wheel -->
                        <div class="absolute inset-2 rounded-full overflow-hidden transition-transform duration-[4000ms] shadow-inner bg-[#121212]"
                            :style="{ transform: `rotate(${wheelRotation}deg)`, transitionTimingFunction: 'cubic-bezier(0.17, 0.67, 0.12, 0.99)' }">
                            <svg viewBox="0 0 100 100" class="w-full h-full">
                                <g transform="translate(50, 50)">
                                    <path v-for="(segment, index) in wheelSegments" :key="index"
                                        :d="`M 0 0 L ${48 * Math.cos((segment.rotation - 90) * Math.PI / 180)} ${48 * Math.sin((segment.rotation - 90) * Math.PI / 180)} A 48 48 0 0 1 ${48 * Math.cos((segment.rotation + 360 / wheelSegments.length - 90) * Math.PI / 180)} ${48 * Math.sin((segment.rotation + 360 / wheelSegments.length - 90) * Math.PI / 180)} Z`"
                                        :fill="segment.color" stroke="rgba(0,0,0,0.3)" stroke-width="0.5" />
                                    <!-- Labels -->
                                    <text v-for="(segment, index) in wheelSegments" :key="'text-' + index"
                                        :transform="`rotate(${segment.rotation + 360 / wheelSegments.length / 2}) translate(30, 0) rotate(90)`"
                                        text-anchor="middle" dominant-baseline="middle"
                                        :fill="segment.percentage === 0 || segment.percentage === 100 ? 'white' : '#1a1a1a'"
                                        font-size="5" font-weight="600" style="font-family: 'Lexend', sans-serif;">
                                        {{ segment.label }}
                                    </text>
                                </g>
                            </svg>
                        </div>

                        <!-- Center Button -->
                        <div
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-20 h-20 bg-[#121212] rounded-full flex items-center justify-center shadow-lg border-2 border-border z-10">
                            <span class="text-[#FFF4E6] font-semibold text-sm tracking-wide">SPIN</span>
                        </div>
                    </div>
                </div>

                <!-- Phone Input & Spin Button -->
                <transition enter-active-class="transition-all duration-500 ease-out"
                    leave-active-class="transition-all duration-300 ease-in" enter-from-class="opacity-0 translate-y-4"
                    leave-to-class="opacity-0 translate-y-4">
                    <Card v-if="!hasSpun" class="bg-card border-border shadow-lg">
                        <CardContent class="p-6">
                            <form @submit.prevent="spin" class="space-y-4">
                                <div>
                                    <Label for="name"
                                        class="text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2 block">Full
                                        Name</Label>
                                    <Input id="name" v-model="form.name" type="text" placeholder="John Doe"
                                        class="bg-background border-border text-foreground placeholder:text-muted-foreground text-center text-base py-5 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                                        :class="{ 'ring-2 ring-primary/30': pulseAnimation }" required />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div>
                                    <Label for="phone"
                                        class="text-muted-foreground text-xs font-medium uppercase tracking-wider mb-2 block">Phone
                                        Number</Label>
                                    <Input id="phone" v-model="form.phone" type="tel" placeholder="+1 (555) 000-0000"
                                        class="bg-background border-border text-foreground placeholder:text-muted-foreground text-center text-base py-5 rounded-lg focus:ring-2 focus:ring-primary/30 focus:border-primary transition-all"
                                        :class="{ 'ring-2 ring-primary/30': pulseAnimation }" required
                                        @focus="pulseAnimation = false" />
                                    <InputError :message="form.errors.phone" />
                                    <InputError :message="(form.errors as any).message" />
                                </div>

                                <Button type="submit"
                                    class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground font-semibold py-5 text-base rounded-lg shadow-md transition-all hover:shadow-lg disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="form.processing || isSpinning">
                                    <Sparkles v-if="!isSpinning" class="w-4 h-4 mr-2" />
                                    <RotateCcw v-else class="w-4 h-4 mr-2 animate-spin" />
                                    {{ isSpinning ? 'Spinning...' : 'Spin the Wheel' }}
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </transition>

                <!-- Result Card -->
                <transition enter-active-class="transition-all duration-700 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-8"
                    enter-to-class="opacity-100 scale-100 translate-y-0">
                    <Card v-if="showResult && !isSpinning"
                        class="bg-card border-border shadow-lg overflow-hidden">
                        <!-- Win Header -->
                        <div v-if="isWin" class="bg-secondary p-4 text-center border-b border-border">
                            <div class="flex items-center justify-center gap-2">
                                <Sparkles class="w-4 h-4 text-primary" />
                                <span class="font-medium text-foreground text-sm tracking-wide">YOU WON</span>
                                <Sparkles class="w-4 h-4 text-primary" />
                            </div>
                        </div>

                        <!-- Try Again Header -->
                        <div v-else class="bg-secondary p-4 text-center border-b border-border">
                            <div class="flex items-center justify-center gap-2">
                                <RotateCcw class="w-4 h-4 text-muted-foreground" />
                                <span class="font-medium text-foreground text-sm tracking-wide">TRY AGAIN</span>
                            </div>
                        </div>

                        <CardContent class="p-6 text-center">
                            <template v-if="isWin">
                                <!-- Prize Display -->
                                <div class="mb-5">
                                    <span class="text-2xl font-semibold text-foreground">
                                        {{ selectedDiscount?.label }}
                                    </span>
                                    <p class="text-muted-foreground text-xs mt-1 uppercase tracking-wider">Your discount</p>
                                </div>

                                <!-- Code Box -->
                                <div class="bg-secondary rounded-lg p-4 mb-4 border border-border">
                                    <p class="text-xs text-muted-foreground uppercase tracking-wider mb-2">Discount Code</p>
                                    <div class="flex items-center justify-between gap-3">
                                        <code
                                            class="text-lg font-mono font-semibold text-foreground tracking-wide">{{ result?.code }}</code>
                                        <Button variant="ghost" size="sm" @click="copyCode"
                                            class="flex items-center gap-1 text-muted-foreground hover:text-foreground hover:bg-accent">
                                            <Check v-if="copied" class="w-3.5 h-3.5 text-primary" />
                                            <Copy v-else class="w-3.5 h-3.5" />
                                            <span class="text-xs">{{ copied ? 'Copied' : 'Copy' }}</span>
                                        </Button>
                                    </div>
                                </div>

                                <!-- Expiry -->
                                <div class="flex items-center justify-center gap-2 text-xs text-muted-foreground mb-5">
                                    <Clock class="w-3.5 h-3.5" />
                                    <span>Expires {{ result?.expiresAt ? formatExpiry(result.expiresAt) : 'soon'
                                        }}</span>
                                </div>

                                <!-- CTA Button -->
                                <Button
                                    class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground font-medium py-4 text-sm rounded-lg shadow-md transition-all hover:shadow-lg">
                                    Start Shopping
                                </Button>
                            </template>

                            <template v-else>
                                <!-- Try Again Display -->
                                <div class="py-4">
                                    <div
                                        class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center mx-auto mb-4 border border-border">
                                        <Sparkles class="w-8 h-8 text-muted-foreground" />
                                    </div>
                                    <h2 class="text-lg font-medium text-foreground mb-2">Almost there!</h2>
                                    <p class="text-muted-foreground text-sm mb-5">Don't give up. Try your luck again.</p>
                                    <Button variant="outline"
                                        class="w-full py-4 text-sm font-medium border-border text-foreground hover:bg-secondary hover:text-foreground"
                                        @click="resetSpin">
                                        <RotateCcw class="w-4 h-4 mr-2" />
                                        Spin Again
                                    </Button>
                                </div>
                            </template>
                        </CardContent>
                    </Card>
                </transition>
            </template>
        </div>
    </div>
</template>

<style>
@keyframes confetti-celebration {
    0% {
        opacity: 1;
        transform: translateY(0) rotate(0deg) scale(1);
    }

    10% {
        opacity: 1;
    }

    100% {
        opacity: 0;
        transform: translateY(100vh) rotate(720deg) scale(0.5);
    }
}
</style>
