<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Spinner } from '@/components/ui/spinner';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: AuthSimpleLayout,
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
    canRegister: boolean;
}>();
</script>

<template>

    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-accent-foreground bg-secondary rounded-lg p-3 border border-border">
        {{ status }}
    </div>

    <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }"
        class="flex flex-col gap-4">
        <div class="grid gap-4">
            <div class="grid gap-2">
                <input id="email" type="email" name="email" required autofocus :tabindex="1" autocomplete="email"
                    placeholder="Email address"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <input id="password" type="password" name="password" required :tabindex="2"
                    autocomplete="current-password" placeholder="Password"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.password" />
            </div>

            <div class="flex items-center justify-between">
                <label for="remember" class="flex items-center gap-2 text-sm text-muted-foreground cursor-pointer">
                    <input type="checkbox" id="remember" name="remember" :tabindex="3"
                        class="w-4 h-4 rounded border-border text-primary focus:ring-primary" />
                    <span>Remember me</span>
                </label>
                <TextLink v-if="canResetPassword" :href="request()" class="text-sm text-accent-foreground hover:text-primary"
                    :tabindex="5">
                    Forgot password?
                </TextLink>
            </div>

            <button type="submit"
                class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground rounded-xl py-3.5 text-base font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                :tabindex="4" :disabled="processing" data-test="login-button">
                <Spinner v-if="processing" />
                <span v-else>Log In</span>
            </button>
        </div>

        <div class="text-center text-sm text-muted-foreground mt-4" v-if="canRegister">
            Don't have an account?
            <TextLink :href="register()" class="text-accent-foreground hover:text-primary font-medium" :tabindex="5">
                Sign up
            </TextLink>
        </div>
    </Form>
</template>
