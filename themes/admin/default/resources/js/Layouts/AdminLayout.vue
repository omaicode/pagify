<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import GlobalSearch from '../Components/GlobalSearch.vue';
import logo from '@img/pagify_icon.png';

const page = usePage();

const menu = computed(() => page.props.menu ?? []);
const currentSite = computed(() => page.props.currentSite ?? null);
const locale = computed(() => page.props.locale ?? 'en');
const admin = computed(() => page.props.auth?.admin ?? null);
const supportedLocales = computed(() => page.props.supportedLocales ?? ['en']);
const localeUpdateUrl = computed(() => page.props.localeUpdateUrl ?? null);
const translation = computed(() => page.props.translations?.ui ?? {});
const currentUrl = computed(() => page.url ?? '/');

const activeHref = computed(() => currentUrl.value.split('?')[0]);

const navItems = computed(() => (Array.isArray(menu.value) ? menu.value : []).slice(0, 6));

const adminInitials = computed(() => {
    const name = `${admin.value?.name ?? ''}`.trim();
    if (!name) {
        return 'A';
    }

    return name
        .split(' ')
        .map((part) => part[0])
        .join('')
        .slice(0, 2)
        .toUpperCase();
});

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
    <div class="pf-page">
        <header class="sticky top-0 z-40 border-b border-[#e5deff] bg-white/95 px-5 py-3 backdrop-blur">
            <div class="mx-auto grid max-w-[1400px] grid-cols-12 items-center gap-3">
                <div class="col-span-12 flex items-center gap-2 md:col-span-3">
                    <div class="h-9 w-9">
                        <img :src="logo" alt="Pagify logo" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#1e1b4b]">Pagify Admin</p>
                        <p class="text-xs text-[#6b7280]">{{ currentSite?.name ?? (translation.no_site ?? 'No site selected') }}</p>
                    </div>
                </div>

                <nav class="col-span-12 flex items-center justify-center gap-2 md:col-span-6">
                    <a
                        v-for="item in navItems"
                        :key="item.route ?? item.href"
                        :href="item.href"
                        :class="item.href === activeHref ? 'pf-nav-pill-active' : 'pf-nav-pill hover:bg-[#f3f0ff]'"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="col-span-12 flex items-center justify-end gap-2 md:col-span-3">
                    <GlobalSearch :items="menu" />
                    <label class="text-xs text-[#6b7280]">
                        {{ translation.change_locale ?? 'Change locale' }}
                        <select :value="locale" class="ml-1 rounded-md border border-[#d8cffc] bg-white px-1.5 py-1 text-xs" @change="updateLocale">
                            <option v-for="item in supportedLocales" :key="item" :value="item">{{ item }}</option>
                        </select>
                    </label>
                    <div class="flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold text-white" style="background-image: var(--pagify-gradient)">
                        {{ adminInitials }}
                    </div>
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-[1400px] p-5">
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
