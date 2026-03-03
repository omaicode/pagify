<script setup>
import { router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    auditLogs: {
        type: Object,
        required: true,
    },
});

const filterForm = reactive({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    site_id: props.filters.site_id ?? '',
    admin_id: props.filters.admin_id ?? '',
});

const submitFilters = () => {
    router.get('/admin/audit-logs', filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <AdminLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">Audit logs</h1>
        </div>

        <form class="mb-4 grid grid-cols-1 gap-2 rounded border border-slate-200 bg-white p-3 md:grid-cols-4" @submit.prevent="submitFilters">
            <input v-model="filterForm.q" type="text" placeholder="Search" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <input v-model="filterForm.action" type="text" placeholder="Action" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <input v-model="filterForm.site_id" type="number" placeholder="Site ID" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <div class="flex gap-2">
                <input v-model="filterForm.admin_id" type="number" placeholder="Admin ID" class="w-full rounded border border-slate-300 px-2 py-1 text-sm">
                <button type="submit" class="rounded bg-slate-900 px-3 py-1 text-sm text-white">Filter</button>
            </div>
        </form>

        <div class="overflow-hidden rounded border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">When</th>
                        <th class="px-3 py-2 text-left">Action</th>
                        <th class="px-3 py-2 text-left">Entity</th>
                        <th class="px-3 py-2 text-left">Admin</th>
                        <th class="px-3 py-2 text-left">Site</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="log in auditLogs.data" :key="log.id">
                        <td class="px-3 py-2">{{ log.created_at }}</td>
                        <td class="px-3 py-2">{{ log.action }}</td>
                        <td class="px-3 py-2">{{ log.entity_type }}#{{ log.entity_id }}</td>
                        <td class="px-3 py-2">{{ log.admin?.name ?? 'System' }}</td>
                        <td class="px-3 py-2">{{ log.site?.name ?? 'Global' }}</td>
                    </tr>
                    <tr v-if="auditLogs.data.length === 0">
                        <td colspan="5" class="px-3 py-6 text-center text-slate-500">No audit logs found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
