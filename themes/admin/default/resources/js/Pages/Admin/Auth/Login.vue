<script setup>
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

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
        <section class="w-full max-w-md rounded-2xl border border-[#e5deff] bg-white p-6 shadow-lg">
            <div class="mb-5 flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl" style="background-image: var(--pagify-gradient)" />
                <div>
                    <h1 class="text-2xl font-semibold text-[#1e1b4b]">{{ t.login_title ?? 'Admin Login' }}</h1>
                    <p class="text-sm text-[#6b7280]">{{ t.login_subtitle ?? 'Sign in to continue.' }}</p>
                </div>
            </div>

            <div v-if="errors.username" class="mb-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ errors.username }}
            </div>

            <form :action="props.loginAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token">

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="username">{{ t.username_or_email ?? 'Username or email' }}</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        class="pf-input"
                        required
                        autofocus
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="password">{{ t.password ?? 'Password' }}</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="pf-input"
                        required
                    >
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    {{ t.remember_me ?? 'Remember me' }}
                </label>

                <button
                    type="submit"
                    class="pf-btn-primary w-full !rounded-lg py-2.5"
                >
                    {{ t.sign_in ?? 'Sign in' }}
                </button>
            </form>
        </section>
    </main>
</template>
