<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Set New Password',
        description: 'Create a strong password for your account',
    },
});

const props = defineProps<{
    token: string;
    email: string;
}>();

const inputEmail = ref(props.email);
</script>

<template>

    <Head title="Reset password" />

    <Form v-bind="update.form()" :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']" v-slot="{ errors, processing }">
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email" class="text-gray-700 font-medium">Email</Label>
                <Input id="email" type="email" name="email" autocomplete="email" v-model="inputEmail"
                    class="rounded-xl border-gray-200 bg-gray-50" readonly />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password" class="text-gray-700 font-medium">New Password</Label>
                <PasswordInput id="password" name="password" autocomplete="new-password" class="rounded-xl" autofocus
                    placeholder="Enter new password" />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="text-gray-700 font-medium">Confirm Password</Label>
                <PasswordInput id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                    class="rounded-xl" placeholder="Confirm new password" />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-base font-semibold"
                :disabled="processing" data-test="reset-password-button">
                <Spinner v-if="processing" />
                <span v-else>Reset Password</span>
            </Button>
        </div>
    </Form>
</template>
