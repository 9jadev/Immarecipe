<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import TextLink from '@/components/TextLink.vue';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: AuthSimpleLayout,
});
</script>

<template>

    <Head title="Register" />

    <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }" class="flex flex-col gap-4">
        <div class="grid gap-4">
            <div class="grid gap-2">
                <input id="name" type="text" required autofocus :tabindex="1" autocomplete="name" name="name"
                    placeholder="Full Name"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <input id="email" type="email" required :tabindex="2" autocomplete="email" name="email"
                    placeholder="Email address"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <input id="password" type="password" required :tabindex="3" autocomplete="new-password" name="password"
                    placeholder="Password"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <input id="password_confirmation" type="password" required :tabindex="4" autocomplete="new-password"
                    name="password_confirmation" placeholder="Confirm Password"
                    class="w-full px-4 py-3.5 rounded-xl border border-border bg-card text-foreground placeholder:text-muted-foreground focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/30 transition-all" />
                <InputError :message="errors.password_confirmation" />
            </div>

            <button type="submit"
                class="w-full bg-primary hover:bg-[var(--primary-hover)] text-primary-foreground rounded-xl py-3.5 text-base font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center"
                tabindex="5" :disabled="processing" data-test="register-user-button">
                <Spinner v-if="processing" />
                <span v-else>Create Account</span>
            </button>
        </div>

        <div class="text-center text-sm text-muted-foreground mt-4">
            Already have an account?
            <TextLink :href="login()" class="text-accent-foreground hover:text-primary font-medium" :tabindex="6">
                Log in
            </TextLink>
        </div>
    </Form>
</template>
