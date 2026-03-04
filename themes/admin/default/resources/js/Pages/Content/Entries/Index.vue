<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

defineProps({
    contentType: {
        type: Object,
        required: true,
    },
    entries: {
        type: Object,
        default: () => ({ data: [] }),
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
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-slate-900">{{ t.entries_for ?? 'Entries for' }} {{ contentType.name }} ({{ contentType.slug }})</h1>
                </div>
                <a :href="routes.create" class="rounded bg-slate-900 px-3 py-2 text-sm text-white hover:bg-slate-800">{{ t.create_entry ?? 'Create entry' }}</a>
            </div>

            <div class="overflow-hidden rounded border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ t.slug ?? 'Slug' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.status ?? 'Status' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.relations ?? 'Relations' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="entry in entries.data" :key="entry.id">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ entry.slug }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ entry.status }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ (entry.relation_slugs ?? []).join(', ') || '-' }}</td>
                            <td class="px-3 py-2">
                                <a :href="entry.routes.edit" class="rounded border border-slate-300 px-2 py-1 text-xs text-slate-700">{{ t.edit ?? 'Edit' }}</a>
                            </td>
                        </tr>
                        <tr v-if="(entries.data ?? []).length === 0">
                            <td colspan="4" class="px-3 py-6 text-center text-slate-500">{{ t.no_entries ?? 'No entries yet.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div>
                <a :href="routes.typeEdit" class="text-sm text-slate-700 underline">{{ t.back_to_content_type ?? 'Back to content type' }}</a>
            </div>
        </div>
    </AdminLayout>
</template>
