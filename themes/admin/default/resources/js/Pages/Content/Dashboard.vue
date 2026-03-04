<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

defineProps({
    title: {
        type: String,
        default: 'Content module',
    },
    description: {
        type: String,
        default: '',
    },
    stats: {
        type: Object,
        default: () => ({ types: 0, entries: 0, publishedEntries: 0 }),
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="pf-section-title">{{ title }}</h1>
                <p class="pf-section-subtitle">{{ description }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <article class="pf-card-highlight">
                    <p class="text-xs uppercase tracking-wide text-white/80">{{ t.content_types ?? 'Content types' }}</p>
                    <p class="text-2xl font-semibold text-white">{{ stats.types }}</p>
                </article>
                <article class="pf-card">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">{{ t.entries ?? 'Entries' }}</p>
                    <p class="text-2xl font-semibold text-[#1e1b4b]">{{ stats.entries }}</p>
                </article>
                <article class="pf-card">
                    <p class="text-xs uppercase tracking-wide text-[#6b7280]">{{ t.published_entries ?? 'Published entries' }}</p>
                    <p class="text-2xl font-semibold text-[#1e1b4b]">{{ stats.publishedEntries }}</p>
                </article>
            </div>

            <div class="flex flex-wrap gap-2">
                <a :href="routes.typesIndex" class="pf-btn-primary">
                    {{ t.manage_content_types ?? 'Manage content types' }}
                </a>
                <a :href="routes.apiEntries" class="pf-btn-outline">
                    {{ t.api_preview_article ?? 'API preview (article)' }}
                </a>
            </div>
        </div>
    </AdminLayout>
</template>
