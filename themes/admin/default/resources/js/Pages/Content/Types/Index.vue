<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

defineProps({
    contentTypes: {
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
                    <h1 class="pf-section-title">{{ t.content_types ?? 'Content types' }}</h1>
                    <p class="pf-section-subtitle">{{ t.content_types_subtitle ?? 'Define and manage content schemas.' }}</p>
                </div>
                <a :href="routes.create" class="pf-btn-primary">
                    {{ t.create_content_type ?? 'Create content type' }}
                </a>
            </div>

            <div class="pf-card overflow-hidden p-0">
                <table class="min-w-full divide-y divide-[#ece8ff] text-sm">
                    <thead class="bg-[#f8f6ff]">
                        <tr>
                            <th class="px-3 py-2 text-left">{{ t.name ?? 'Name' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.slug ?? 'Slug' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.status ?? 'Status' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.updated_at ?? 'Updated at' }}</th>
                            <th class="px-3 py-2 text-left">{{ t.actions ?? 'Actions' }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="contentType in contentTypes.data" :key="contentType.id">
                            <td class="px-3 py-2 font-medium text-[#1e1b4b]">{{ contentType.name }}</td>
                            <td class="px-3 py-2 text-[#6b7280]">{{ contentType.slug }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded px-2 py-1 text-xs" :class="contentType.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                    {{ contentType.is_active ? (t.active ?? 'Active') : (t.inactive ?? 'Inactive') }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-[#6b7280]">{{ contentType.updated_at ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <a :href="contentType.routes.edit" class="pf-btn-outline !px-2 !py-1 !text-xs">{{ t.edit ?? 'Edit' }}</a>
                                    <a :href="contentType.routes.entries" class="pf-btn-outline !px-2 !py-1 !text-xs">{{ t.entries ?? 'Entries' }}</a>
                                    <a :href="contentType.routes.builder" class="pf-btn-outline !px-2 !py-1 !text-xs">{{ t.builder ?? 'Builder' }}</a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(contentTypes.data ?? []).length === 0">
                            <td colspan="5" class="px-3 py-6 text-center text-slate-500">{{ t.no_content_types ?? 'No content types yet.' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
