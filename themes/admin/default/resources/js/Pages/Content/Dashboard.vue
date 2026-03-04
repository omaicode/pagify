<script setup>
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
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ title }}</h1>
                <p class="text-sm text-slate-600">{{ description }}</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                <article class="rounded border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Content types</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ stats.types }}</p>
                </article>
                <article class="rounded border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Entries</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ stats.entries }}</p>
                </article>
                <article class="rounded border border-slate-200 bg-white p-4">
                    <p class="text-xs uppercase tracking-wide text-slate-500">Published entries</p>
                    <p class="text-2xl font-semibold text-slate-900">{{ stats.publishedEntries }}</p>
                </article>
            </div>

            <div class="flex flex-wrap gap-2">
                <a :href="routes.typesIndex" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    Manage content types
                </a>
                <a :href="routes.apiEntries" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                    API preview (article)
                </a>
            </div>
        </div>
    </AdminLayout>
</template>
