<script setup>
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    contentType: { type: Object, required: true },
    plans: { type: Object, default: () => ({ data: [] }) },
    routes: { type: Object, default: () => ({}) },
});

const retryPlan = (planId) => {
    const url = (props.routes.retry ?? '').replace('__PLAN_ID__', String(planId));
    router.post(url);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="pf-section-title">Schema migration plans for {{ contentType.name }}</h1>

            <ul class="space-y-2">
                <li v-for="plan in plans.data" :key="plan.id" class="pf-card text-sm text-[#1e1b4b]">
                    <strong>#{{ plan.id }}</strong> [{{ plan.status }}]
                    <span v-if="plan.planned_at"> planned at {{ plan.planned_at }}</span>
                    <span v-if="plan.execution_started_at"> · started {{ plan.execution_started_at }}</span>
                    <span v-if="plan.executed_at"> · executed {{ plan.executed_at }}</span>
                    <div>
                        additions: {{ plan.summary?.additions ?? 0 }}, removals: {{ plan.summary?.removals ?? 0 }}, updates: {{ plan.summary?.updates ?? 0 }}
                    </div>
                    <div>attempts: {{ plan.execution_attempts ?? 0 }}</div>
                    <div v-if="plan.error_message" class="text-rose-700">Error: {{ plan.error_message }}</div>
                    <button
                        v-if="['failed', 'retryable'].includes(plan.status)"
                        type="button"
                        class="pf-btn-outline mt-2 !px-2 !py-1 !text-xs"
                        @click="retryPlan(plan.id)"
                    >
                        Retry
                    </button>
                </li>
                <li v-if="(plans.data ?? []).length === 0" class="pf-card text-sm text-[#6b7280]">No migration plans yet.</li>
            </ul>

            <div class="flex flex-wrap gap-2">
                <a :href="routes.builderEdit" class="pf-btn-outline">Back to builder</a>
                <a :href="routes.typeEdit" class="pf-btn-outline">Back to content type edit</a>
            </div>
        </div>
    </AdminLayout>
</template>
