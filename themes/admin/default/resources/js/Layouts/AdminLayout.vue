<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import Sidebar from '../Components/Sidebar.vue';
import GlobalSearch from '../Components/GlobalSearch.vue';

const page = usePage();

const menu = computed(() => page.props.menu ?? []);
const currentSite = computed(() => page.props.currentSite ?? null);
const locale = computed(() => page.props.locale ?? 'en');
const admin = computed(() => page.props.auth?.admin ?? null);
const supportedLocales = computed(() => page.props.supportedLocales ?? ['en']);
const localeUpdateUrl = computed(() => page.props.localeUpdateUrl ?? null);
const translation = computed(() => page.props.translations?.ui ?? {});

const updateLocale = async (event) => {
    const nextLocale = event.target.value;

    if (!localeUpdateUrl.value || !nextLocale) {
        return;
    }

    await axios.post(localeUpdateUrl.value, {
        locale: nextLocale,
    });

    window.location.reload();
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 text-slate-900">
        <header class="flex items-center justify-between border-b border-slate-200 bg-white px-4 py-3">
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-500">{{ translation.admin ?? 'Admin' }}</p>
                <p class="text-sm font-semibold">{{ currentSite?.name ?? (translation.no_site ?? 'No site selected') }}</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs text-slate-600">
                    {{ translation.change_locale ?? 'Change locale' }}
                    <select :value="locale" class="ml-1 rounded border border-slate-300 px-1 py-0.5 text-xs" @change="updateLocale">
                        <option v-for="item in supportedLocales" :key="item" :value="item">{{ item }}</option>
                    </select>
                </label>
                <span class="text-sm font-medium">{{ admin?.name ?? (translation.guest ?? 'Guest') }}</span>
                <GlobalSearch :items="menu" />
            </div>
        </header>

        <div class="grid min-h-[calc(100vh-57px)] grid-cols-12">
            <aside class="col-span-12 border-r border-slate-200 bg-white p-4 md:col-span-3 lg:col-span-2">
                <Sidebar :items="menu" />
            </aside>
            <main class="col-span-12 p-4 md:col-span-9 lg:col-span-10">
                <slot />
            </main>
        </div>
    </div>
</template>
