<script setup>
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import GlobalSearch from '../Components/GlobalSearch.vue';
import TopNav from '../Components/TopNav.vue';
import LocaleDropdown from '../Components/LocaleDropdown.vue';
import AvatarMenu from '../Components/AvatarMenu.vue';
import logo from '@img/pagify_icon.png';

const page = usePage();
const menu = computed(() => page.props.menu ?? []);
const currentSite = computed(() => page.props.currentSite ?? null);
const locale = computed(() => page.props.locale ?? 'en');
const admin = computed(() => page.props.auth?.admin ?? null);
const supportedLocales = computed(() => page.props.supportedLocales ?? ['en']);
const localeUpdateUrl = computed(() => page.props.localeUpdateUrl ?? null);
const settingsUrl = computed(() => page.props.settingsUrl ?? '#');
const translation = computed(() => page.props.translations?.ui ?? {});
const currentUrl = computed(() => page.url ?? '/');

const activeHref = computed(() => currentUrl.value.split('?')[0]);

const navItems = computed(() => (Array.isArray(menu.value) ? menu.value : []));

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

const logout = () => {
    router.post('/admin/logout');
};

const switchLocale = async (nextLocale) => {
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
                <div class="order-1 col-span-6 flex items-center gap-2 md:col-span-3 md:order-1">
                    <div class="h-9 w-9">
                        <img :src="logo" alt="Pagify logo" class="h-full w-full object-cover">
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-[#1e1b4b]">{{ translation.app_title ?? 'Pagify Admin' }}</p>
                        <p class="text-xs text-[#6b7280]">{{ currentSite?.name ?? (translation.no_site ?? 'No site selected') }}</p>
                    </div>
                </div>

                <div class="order-3 col-span-12 md:col-span-6 md:order-2">
                    <TopNav :items="navItems" :active-href="activeHref" />
                </div>

                <div class="order-2 col-span-6 flex items-center justify-end gap-2 md:col-span-3 md:order-3">
                    <GlobalSearch :items="menu" />

                    <LocaleDropdown
                        :locale="locale"
                        :supported-locales="supportedLocales"
                        @select="switchLocale"
                    />

                    <AvatarMenu
                        :initials="adminInitials"
                        :settings-href="settingsUrl"
                        @logout="logout"
                    />
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
