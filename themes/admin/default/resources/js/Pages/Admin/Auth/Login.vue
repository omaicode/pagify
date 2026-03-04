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
    <Head title="Admin Login" />

    <main class="flex min-h-screen items-center justify-center bg-slate-50 px-4 py-10">
        <section class="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="mb-1 text-2xl font-semibold text-slate-900">{{ t.admin ?? 'Admin' }} Login</h1>
            <p class="mb-6 text-sm text-slate-600">Sign in to continue.</p>

            <div v-if="errors.username" class="mb-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
                {{ errors.username }}
            </div>

            <form :action="props.loginAction" method="POST" class="space-y-4">
                <input type="hidden" name="_token" :value="page.props.csrf_token">

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="username">Username or email</label>
                    <input
                        id="username"
                        name="username"
                        type="text"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500"
                        required
                        autofocus
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700" for="password">Password</label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        class="w-full rounded border border-slate-300 px-3 py-2 text-sm outline-none focus:border-slate-500"
                        required
                    >
                </div>

                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input name="remember" type="checkbox" value="1" class="rounded border-slate-300">
                    Remember me
                </label>

                <button
                    type="submit"
                    class="w-full rounded bg-slate-900 px-3 py-2 text-sm font-medium text-white transition hover:bg-slate-800"
                >
                    Sign in
                </button>
            </form>
        </section>
    </main>
</template>
