<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import AdminLayout from '../../../Layouts/AdminLayout.vue';

const props = defineProps({
    filters: {
        type: Object,
        default: () => ({}),
    },
    filterOptions: {
        type: Object,
        default: () => ({
            actions: [],
            entity_types: [],
            sites: [],
            admins: [],
        }),
    },
    auditLogs: {
        type: Object,
        required: true,
    },
});

const filterForm = reactive({
    q: props.filters.q ?? '',
    action: props.filters.action ?? '',
    entity_type: props.filters.entity_type ?? '',
    entity_id: props.filters.entity_id ?? '',
    site_id: props.filters.site_id ?? '',
    admin_id: props.filters.admin_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    per_page: props.filters.per_page ?? 20,
    sort_by: props.filters.sort_by ?? 'created_at',
    sort_dir: props.filters.sort_dir ?? 'desc',
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const label = (key, fallback) => {
    return t.value?.[key] ?? fallback;
};

const submitFilters = () => {
    router.get('/admin/audit-logs', filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resetFilters = () => {
    filterForm.q = '';
    filterForm.action = '';
    filterForm.entity_type = '';
    filterForm.entity_id = '';
    filterForm.site_id = '';
    filterForm.admin_id = '';
    filterForm.date_from = '';
    filterForm.date_to = '';
    filterForm.per_page = 20;
    filterForm.sort_by = 'created_at';
    filterForm.sort_dir = 'desc';

    submitFilters();
};
</script>

<template>
    <AdminLayout>
        <div class="mb-4 flex items-center justify-between">
            <h1 class="text-xl font-semibold">{{ label('audit_logs', 'Audit logs') }}</h1>
        </div>

        <form class="mb-4 grid grid-cols-1 gap-2 rounded border border-slate-200 bg-white p-3 md:grid-cols-5" @submit.prevent="submitFilters">
            <input v-model="filterForm.q" type="text" :placeholder="label('search', 'Search')" class="rounded border border-slate-300 px-2 py-1 text-sm">

            <select v-model="filterForm.action" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">{{ label('all_actions', 'All actions') }}</option>
                <option v-for="action in filterOptions.actions" :key="action" :value="action">{{ action }}</option>
            </select>

            <select v-model="filterForm.entity_type" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">{{ label('all_entity_types', 'All entity types') }}</option>
                <option v-for="entityType in filterOptions.entity_types" :key="entityType" :value="entityType">{{ entityType }}</option>
            </select>

            <input v-model="filterForm.entity_id" type="text" :placeholder="label('entity_id', 'Entity ID')" class="rounded border border-slate-300 px-2 py-1 text-sm">

            <select v-model="filterForm.site_id" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">{{ label('all_sites', 'All sites') }}</option>
                <option v-for="site in filterOptions.sites" :key="site.id" :value="site.id">{{ site.name }}</option>
            </select>

            <select v-model="filterForm.admin_id" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="">{{ label('all_admins', 'All admins') }}</option>
                <option v-for="admin in filterOptions.admins" :key="admin.id" :value="admin.id">{{ admin.name }}</option>
            </select>

            <input v-model="filterForm.date_from" type="date" class="rounded border border-slate-300 px-2 py-1 text-sm">
            <input v-model="filterForm.date_to" type="date" class="rounded border border-slate-300 px-2 py-1 text-sm">

            <select v-model="filterForm.per_page" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option :value="10">{{ label('per_page_10', '10 / page') }}</option>
                <option :value="20">{{ label('per_page_20', '20 / page') }}</option>
                <option :value="50">{{ label('per_page_50', '50 / page') }}</option>
                <option :value="100">{{ label('per_page_100', '100 / page') }}</option>
            </select>

            <select v-model="filterForm.sort_by" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="created_at">{{ label('sort_created_at', 'Sort by created at') }}</option>
                <option value="id">{{ label('sort_id', 'Sort by ID') }}</option>
                <option value="action">{{ label('sort_action', 'Sort by action') }}</option>
                <option value="entity_type">{{ label('sort_entity_type', 'Sort by entity type') }}</option>
            </select>

            <select v-model="filterForm.sort_dir" class="rounded border border-slate-300 px-2 py-1 text-sm">
                <option value="desc">{{ label('newest_first', 'Newest first') }}</option>
                <option value="asc">{{ label('oldest_first', 'Oldest first') }}</option>
            </select>

            <div class="flex gap-2 md:col-span-5">
                <button type="submit" class="rounded bg-slate-900 px-3 py-1 text-sm text-white">{{ label('filter', 'Filter') }}</button>
                <button type="button" class="rounded border border-slate-300 px-3 py-1 text-sm text-slate-700" @click="resetFilters">{{ label('reset', 'Reset') }}</button>
            </div>
        </form>

        <div class="overflow-hidden rounded border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">{{ label('when', 'When') }}</th>
                        <th class="px-3 py-2 text-left">{{ label('action', 'Action') }}</th>
                        <th class="px-3 py-2 text-left">{{ label('entity', 'Entity') }}</th>
                        <th class="px-3 py-2 text-left">{{ label('ip', 'IP') }}</th>
                        <th class="px-3 py-2 text-left">{{ label('admin', 'Admin') }}</th>
                        <th class="px-3 py-2 text-left">{{ label('site', 'Site') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <tr v-for="log in auditLogs.data" :key="log.id">
                        <td class="px-3 py-2">{{ log.created_at }}</td>
                        <td class="px-3 py-2">{{ log.action }}</td>
                        <td class="px-3 py-2">{{ log.entity_type }}#{{ log.entity_id }}</td>
                        <td class="px-3 py-2">{{ log.ip_address ?? '-' }}</td>
                        <td class="px-3 py-2">{{ log.admin?.name ?? label('system', 'System') }}</td>
                        <td class="px-3 py-2">{{ log.site?.name ?? label('global', 'Global') }}</td>
                    </tr>
                    <tr v-if="auditLogs.data.length === 0">
                        <td colspan="6" class="px-3 py-6 text-center text-slate-500">{{ label('no_audit_logs', 'No audit logs found.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-1">
            <Link
                v-for="link in auditLogs.links"
                :key="`${link.label}-${link.url}`"
                :href="link.url ?? '#'
                "
                :class="[
                    'rounded border px-2 py-1 text-xs',
                    link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-300 text-slate-700',
                    !link.url ? 'pointer-events-none opacity-50' : '',
                ]"
                v-html="link.label"
            />
        </div>
    </AdminLayout>
</template>
