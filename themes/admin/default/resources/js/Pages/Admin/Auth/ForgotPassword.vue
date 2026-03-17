<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import LogoIcon from '@img/pagify_icon.png';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiInput from '@admin-theme/Components/UI/UiInput.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';

const props = defineProps({
    submitAction: {
        type: String,
        required: true,
    },
    loginUrl: {
        type: String,
        required: true,
    },
    statusMessage: {
        type: String,
        default: null,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const errors = computed(() => page.props.errors ?? {});
</script>

<template>
    <Head :title="t.forgot_password_page_title ?? 'Forgot Password'" />

    <main class="pf-page flex min-h-screen items-center justify-center px-4 py-10">
        <UiCard tag="section" class="w-full max-w-md rounded-2xl p-6 shadow-lg">
            <div class="mb-5 flex items-center gap-2">
                <div class="h-12 w-12">
                    <img :src="LogoIcon" alt="Pagify Logo" class="h-full w-full object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-[#1e1b4b]">{{ t.forgot_password_title ?? 'Forgot Password' }}</h1>
                    <p class="text-sm text-[#6b7280]">{{ t.forgot_password_subtitle ?? 'Enter your email to receive a reset link.' }}</p>
                </div>
            </div>

            <UiAlert v-if="errors.email" tone="danger" class="mb-4">
                {{ errors.email }}
            </UiAlert>

            <UiAlert v-if="props.statusMessage" tone="success" class="mb-4">
                {{ props.statusMessage }}
            </UiAlert>

            <form :action="props.submitAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token">

                <UiField :label="t.email ?? 'Email'" for="email" label-tone="muted">
                    <UiInput id="email" name="email" type="email" required autofocus />
                </UiField>

                <UiButton type="submit" radius="lg" full-width size="lg">
                    {{ t.send_reset_link ?? 'Send reset link' }}
                </UiButton>
            </form>

            <div class="mt-4 text-sm">
                <a :href="props.loginUrl" class="text-[#4338ca] hover:underline">{{ t.back_to_sign_in ?? 'Back to sign in' }}</a>
            </div>
        </UiCard>
    </main>
</template>
