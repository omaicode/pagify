<script setup>
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    contentType: { type: Object, required: true },
    plans: { type: Object, default: () => ({ data: [] }) },
    routes: { type: Object, default: () => ({}) },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const retryPlan = (planId) => {
    const url = (props.routes.retry ?? '').replace('__PLAN_ID__', String(planId));
    router.post(url);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="pf-section-title">{{ t.schema_migration_plans_for ?? 'Schema migration plans for' }} {{ contentType.name }}</h1>

            <ul class="space-y-2">
                <li v-for="plan in plans.data" :key="plan.id" class="pf-card text-sm text-[#1e1b4b]">
                    <strong>#{{ plan.id }}</strong> [{{ plan.status }}]
                    <span v-if="plan.planned_at"> {{ t.planned_at ?? 'planned at' }} {{ plan.planned_at }}</span>
                    <span v-if="plan.execution_started_at"> · {{ t.started_at ?? 'started' }} {{ plan.execution_started_at }}</span>
                    <span v-if="plan.executed_at"> · {{ t.executed_at ?? 'executed' }} {{ plan.executed_at }}</span>
                    <div>
                        {{ t.additions ?? 'additions' }}: {{ plan.summary?.additions ?? 0 }}, {{ t.removals ?? 'removals' }}: {{ plan.summary?.removals ?? 0 }}, {{ t.updates ?? 'updates' }}: {{ plan.summary?.updates ?? 0 }}
                    </div>
                    <div>{{ t.attempts ?? 'attempts' }}: {{ plan.execution_attempts ?? 0 }}</div>
                    <div v-if="plan.error_message" class="text-rose-700">{{ t.error ?? 'Error' }}: {{ plan.error_message }}</div>
                    <button
                        v-if="['failed', 'retryable'].includes(plan.status)"
                        type="button"
                        class="pf-btn-outline mt-2 !px-2 !py-1 !text-xs"
                        @click="retryPlan(plan.id)"
                    >
                        {{ t.retry ?? 'Retry' }}
                    </button>
                </li>
                <li v-if="(plans.data ?? []).length === 0" class="pf-card text-sm text-[#6b7280]">{{ t.no_migration_plans ?? 'No migration plans yet.' }}</li>
            </ul>

            <div class="flex flex-wrap gap-2">
                <a :href="routes.builderEdit" class="pf-btn-outline">{{ t.back_to_builder ?? 'Back to builder' }}</a>
                <a :href="routes.typeEdit" class="pf-btn-outline">{{ t.back_to_content_type_edit ?? 'Back to content type edit' }}</a>
            </div>
        </div>
    </AdminLayout>
</template>
