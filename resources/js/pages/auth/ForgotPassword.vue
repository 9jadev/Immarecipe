<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/StorefrontLayout.vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Forgot password" />
    <AppLayout>
        <div class="mx-auto w-full max-w-md px-4 py-12">
            <div class="rounded-3xl border border-border bg-card p-8 shadow-lg">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold text-foreground">Forgot password</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Enter your email to receive a reset link.
                    </p>
                </div>

                <div v-if="status"
                    class="mb-4 rounded-xl border border-border bg-secondary p-3 text-center text-sm font-medium text-accent-foreground">
                    {{ status }}
                </div>

                <div class="space-y-5">
                    <Form v-bind="email.form()" v-slot="{ errors, processing }">
                        <div class="grid gap-2">
                            <Label for="email">Email address</Label>
                            <Input id="email" type="email" name="email" autocomplete="off" autofocus
                                placeholder="email@example.com"
                                class="rounded-xl border-border bg-background text-foreground placeholder:text-muted-foreground focus:border-primary focus:ring-primary/30" />
                            <InputError :message="errors.email" />
                        </div>

                        <div class="mt-6">
                            <Button class="w-full rounded-full py-6 text-base font-semibold" :disabled="processing"
                                data-test="email-password-reset-link-button">
                                <Spinner v-if="processing" />
                                <span v-else>Send Reset Link</span>
                            </Button>
                        </div>
                    </Form>

                    <div class="border-t border-border pt-2 text-center text-sm text-muted-foreground">
                        Remember your password?
                        <TextLink :href="login()" class="font-semibold text-accent-foreground hover:text-primary">
                            Log in
                        </TextLink>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
