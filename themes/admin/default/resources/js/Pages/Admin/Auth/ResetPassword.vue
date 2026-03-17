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
    token: {
        type: String,
        required: true,
    },
    email: {
        type: String,
        default: '',
    },
    submitAction: {
        type: String,
        required: true,
    },
    loginUrl: {
        type: String,
        required: true,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const errors = computed(() => page.props.errors ?? {});
</script>

<template>
    <Head :title="t.reset_password_page_title ?? 'Reset Password'" />

    <main class="pf-page flex min-h-screen items-center justify-center px-4 py-10">
        <UiCard tag="section" class="w-full max-w-md rounded-2xl p-6 shadow-lg">
            <div class="mb-5 flex items-center gap-2">
                <div class="h-12 w-12">
                    <img :src="LogoIcon" alt="Pagify Logo" class="h-full w-full object-contain">
                </div>
                <div>
                    <h1 class="text-2xl font-semibold text-[#1e1b4b]">{{ t.reset_password_title ?? 'Reset Password' }}</h1>
                    <p class="text-sm text-[#6b7280]">{{ t.reset_password_subtitle ?? 'Create a new password for your admin account.' }}</p>
                </div>
            </div>

            <UiAlert v-if="errors.email" tone="danger" class="mb-4">
                {{ errors.email }}
            </UiAlert>

            <form :action="props.submitAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token">
                <input type="hidden" name="token" :value="props.token">

                <UiField :label="t.email ?? 'Email'" for="email" label-tone="muted">
                    <UiInput id="email" name="email" type="email" required :value="props.email" autofocus />
                </UiField>

                <UiField :label="t.password ?? 'Password'" for="password" label-tone="muted">
                    <UiInput id="password" name="password" type="password" required />
                </UiField>

                <UiField :label="t.password_confirmation ?? 'Confirm password'" for="password_confirmation" label-tone="muted">
                    <UiInput id="password_confirmation" name="password_confirmation" type="password" required />
                </UiField>

                <UiButton type="submit" radius="lg" full-width size="lg">
                    {{ t.reset_password_submit ?? 'Reset password' }}
                </UiButton>
            </form>

            <div class="mt-4 text-sm">
                <a :href="props.loginUrl" class="text-[#4338ca] hover:underline">{{ t.back_to_sign_in ?? 'Back to sign in' }}</a>
            </div>
        </UiCard>
    </main>
</template>
