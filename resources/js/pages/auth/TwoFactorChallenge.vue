<script setup lang="ts">
import { Form, Head, setLayoutProps } from '@inertiajs/vue3';
import { computed, ref, watchEffect } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { store } from '@/routes/two-factor/login';
import type { TwoFactorConfigContent } from '@/types';

const authConfigContent = computed<TwoFactorConfigContent>(() => {
    if (showRecoveryInput.value) {
        return {
            title: 'Recovery Code',
            description: 'Enter one of your emergency recovery codes',
            buttonText: 'use authentication code',
        };
    }

    return {
        title: 'Two-Factor Auth',
        description: 'Enter the code from your authenticator app',
        buttonText: 'use recovery code',
    };
});

watchEffect(() => {
    setLayoutProps({
        title: authConfigContent.value.title,
        description: authConfigContent.value.description,
    });
});

const showRecoveryInput = ref<boolean>(false);

const toggleRecoveryMode = (clearErrors: () => void): void => {
    showRecoveryInput.value = !showRecoveryInput.value;
    clearErrors();
    code.value = '';
};

const code = ref<string>('');
</script>

<template>

    <Head title="Two-factor authentication" />

    <div class="space-y-5">
        <template v-if="!showRecoveryInput">
            <Form v-bind="store.form()" class="space-y-5" reset-on-error @error="code = ''"
                #default="{ errors, processing, clearErrors }">
                <input type="hidden" name="code" :value="code" />
                <div class="flex flex-col items-center justify-center space-y-3 text-center">
                    <div class="flex w-full items-center justify-center">
                        <InputOTP id="otp" v-model="code" :maxlength="6" :disabled="processing" autofocus>
                            <InputOTPGroup>
                                <InputOTPSlot v-for="index in 6" :key="index" :index="index - 1" />
                            </InputOTPGroup>
                        </InputOTP>
                    </div>
                    <InputError :message="errors.code" />
                </div>
                <Button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-base font-semibold"
                    :disabled="processing">
                    Continue
                </Button>
                <div class="text-center text-sm text-gray-600">
                    <span>or </span>
                    <button type="button" class="text-green-600 hover:text-green-700 font-medium"
                        @click="() => toggleRecoveryMode(clearErrors)">
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>

        <template v-else>
            <Form v-bind="store.form()" class="space-y-5" reset-on-error #default="{ errors, processing, clearErrors }">
                <Input name="recovery_code" type="text" placeholder="Enter recovery code"
                    class="rounded-xl border-gray-200 focus:border-green-500 focus:ring-green-500"
                    :autofocus="showRecoveryInput" required />
                <InputError :message="errors.recovery_code" />
                <Button type="submit"
                    class="w-full bg-green-600 hover:bg-green-700 text-white rounded-full shadow-lg hover:shadow-xl transition-all py-6 text-base font-semibold"
                    :disabled="processing">
                    Continue
                </Button>

                <div class="text-center text-sm text-gray-600">
                    <span>or </span>
                    <button type="button" class="text-green-600 hover:text-green-700 font-medium"
                        @click="() => toggleRecoveryMode(clearErrors)">
                        {{ authConfigContent.buttonText }}
                    </button>
                </div>
            </Form>
        </template>
    </div>
</template>
