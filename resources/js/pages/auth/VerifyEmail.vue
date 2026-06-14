<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        title: 'Verify Email',
        description: 'Please verify your email address to continue',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>

    <Head title="Email verification" />

    <div v-if="status === 'verification-link-sent'"
        class="mb-4 text-center text-sm font-medium text-green-600 bg-green-50 rounded-xl p-3">
        A new verification link has been sent to your email address.
    </div>

    <Form v-bind="send.form()" class="space-y-5 text-center" v-slot="{ processing }">
        <Button :disabled="processing"
            class="bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-base font-semibold">
            <Spinner v-if="processing" />
            <span v-else>Resend Verification Email</span>
        </Button>

        <TextLink :href="logout()" as="button" class="mx-auto block text-sm text-gray-600 hover:text-green-600">
            Log out
        </TextLink>
    </Form>
</template>
