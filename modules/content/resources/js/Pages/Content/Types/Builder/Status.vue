<script setup>
import AdminLayout from '../../../../../../../core/resources/js/Layouts/AdminLayout.vue';

defineProps({
    contentType: { type: Object, required: true },
    plans: { type: Object, default: () => ({ data: [] }) },
    routes: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-semibold text-slate-900">Schema migration plans for {{ contentType.name }}</h1>

            <ul class="space-y-2">
                <li v-for="plan in plans.data" :key="plan.id" class="rounded border border-slate-200 bg-white p-3 text-sm text-slate-700">
                    <strong>#{{ plan.id }}</strong> [{{ plan.status }}]
                    <span v-if="plan.planned_at"> planned at {{ plan.planned_at }}</span>
                    <div>
                        additions: {{ plan.summary?.additions ?? 0 }}, removals: {{ plan.summary?.removals ?? 0 }}, updates: {{ plan.summary?.updates ?? 0 }}
                    </div>
                    <div v-if="plan.error_message" class="text-rose-700">Error: {{ plan.error_message }}</div>
                </li>
                <li v-if="(plans.data ?? []).length === 0" class="rounded border border-slate-200 bg-white p-3 text-sm text-slate-500">No migration plans yet.</li>
            </ul>

            <div class="flex flex-wrap gap-2">
                <a :href="routes.builderEdit" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back to builder</a>
                <a :href="routes.typeEdit" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back to content type edit</a>
            </div>
        </div>
    </AdminLayout>
</template>
