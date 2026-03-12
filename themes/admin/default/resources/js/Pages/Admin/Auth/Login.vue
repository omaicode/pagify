<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiInput from '@admin-theme/Components/UI/UiInput.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';

const props = defineProps({
    loginAction: {
        type: String,
        required: true,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const errors = computed(() => page.props.errors ?? {});
</script>

<template>
    <Head :title="t.login_page_title ?? 'Admin Login'" />

    <main class="pf-page flex min-h-screen items-center justify-center px-4 py-10">
        <UiCard tag="section" class="w-full max-w-md rounded-2xl p-6 shadow-lg">
            <div class="mb-5 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl" style="background-image: var(--pagify-gradient)" />
                <div>
                    <h1 class="text-2xl font-semibold text-[#1e1b4b]">{{ t.login_title ?? 'Admin Login' }}</h1>
                    <p class="text-sm text-[#6b7280]">{{ t.login_subtitle ?? 'Sign in to continue.' }}</p>
                </div>
            </div>

            <UiAlert v-if="errors.username" tone="danger" class="mb-4">
                {{ errors.username }}
            </UiAlert>

            <form :action="props.loginAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token">

                <UiField :label="t.username_or_email ?? 'Username or email'" for="username" label-tone="muted">
                    <UiInput
                        id="username"
                        name="username"
                        type="text"
                        required
                        autofocus
                    />
                </UiField>

                <UiField :label="t.password ?? 'Password'" for="password" label-tone="muted">
                    <UiInput
                        id="password"
                        name="password"
                        type="password"
                        required
                    />
                </UiField>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    {{ t.remember_me ?? 'Remember me' }}
                </label>

                <UiButton type="submit" radius="lg" full-width size="lg">
                    {{ t.sign_in ?? 'Sign in' }}
                </UiButton>
            </form>
        </UiCard>
    </main>
</template>
