<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    title?: string;
    subtitle?: string | null;
    showText?: boolean;
    size?: 'sm' | 'md' | 'lg';
    logoSrc?: string;
};

const props = withDefaults(defineProps<Props>(), {
    subtitle: 'Pastry',
    showText: true,
    size: 'md',
    logoSrc: '/logo.svg',
});

const page = usePage();
const logoFailed = ref(false);

const resolvedTitle = computed(() => {
    const fromProps = props.title?.trim();
    if (fromProps) return fromProps;

    const fromServer = (page.props.name as string | undefined)?.trim();
    if (fromServer) return fromServer;

    return 'Immarecipe';
});

const containerSizeClass = computed(() => {
    if (props.size === 'lg') return 'size-10';
    if (props.size === 'sm') return 'size-7';
    return 'size-8';
});

const iconSizeClass = computed(() => {
    if (props.size === 'lg') return 'size-6';
    if (props.size === 'sm') return 'size-4';
    return 'size-5';
});

const onLogoError = () => {
    logoFailed.value = true;
};
</script>

<template>
    <div class="flex items-center" v-bind="$attrs">
        <div
            class="flex aspect-square items-center justify-center overflow-hidden rounded-md bg-sidebar-primary text-sidebar-primary-foreground"
            :class="containerSizeClass"
        >
            <img
                v-if="!logoFailed"
                :src="props.logoSrc"
                :alt="resolvedTitle"
                class="h-full w-full object-contain"
                @error="onLogoError"
            />
            <AppLogoIcon
                v-else
                class="fill-current"
                :class="iconSizeClass"
            />
        </div>

        <div v-if="props.showText" class="ml-2 grid min-w-0 flex-1 text-left">
            <span class="truncate text-sm font-semibold leading-tight">
                {{ resolvedTitle }}
            </span>
            <span
                v-if="props.subtitle"
                class="truncate text-xs leading-tight opacity-80"
            >
                {{ props.subtitle }}
            </span>
        </div>
    </div>
</template>
