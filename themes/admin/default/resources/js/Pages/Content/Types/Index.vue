<script setup>
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
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="pf-section-title">Content types</h1>
                    <p class="pf-section-subtitle">Define and manage content schemas.</p>
                </div>
                <a :href="routes.create" class="pf-btn-primary">
                    Create content type
                </a>
            </div>

            <div class="pf-card overflow-hidden p-0">
                <table class="min-w-full divide-y divide-[#ece8ff] text-sm">
                    <thead class="bg-[#f8f6ff]">
                        <tr>
                            <th class="px-3 py-2 text-left">Name</th>
                            <th class="px-3 py-2 text-left">Slug</th>
                            <th class="px-3 py-2 text-left">Status</th>
                            <th class="px-3 py-2 text-left">Updated at</th>
                            <th class="px-3 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="contentType in contentTypes.data" :key="contentType.id">
                            <td class="px-3 py-2 font-medium text-[#1e1b4b]">{{ contentType.name }}</td>
                            <td class="px-3 py-2 text-[#6b7280]">{{ contentType.slug }}</td>
                            <td class="px-3 py-2">
                                <span class="rounded px-2 py-1 text-xs" :class="contentType.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'">
                                    {{ contentType.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-[#6b7280]">{{ contentType.updated_at ?? '-' }}</td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap gap-1">
                                    <a :href="contentType.routes.edit" class="pf-btn-outline !px-2 !py-1 !text-xs">Edit</a>
                                    <a :href="contentType.routes.entries" class="pf-btn-outline !px-2 !py-1 !text-xs">Entries</a>
                                    <a :href="contentType.routes.builder" class="pf-btn-outline !px-2 !py-1 !text-xs">Builder</a>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="(contentTypes.data ?? []).length === 0">
                            <td colspan="5" class="px-3 py-6 text-center text-slate-500">No content types yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
