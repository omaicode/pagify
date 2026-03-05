<script setup>
import { router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import UiStatusBadge from '@admin-theme/Components/UI/UiStatusBadge.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';

const props = defineProps({
    page: { type: Object, required: true },
    revisions: { type: Array, default: () => [] },
    leftRevision: { type: Object, default: null },
    rightRevision: { type: Object, default: null },
    diff: { type: Object, default: () => ({ changed: false, changes: [] }) },
    routes: { type: Object, default: () => ({}) },
});

const pageContext = usePage();
const t = computed(() => pageContext.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const rollback = async (revisionId) => {
    const result = await Swal.fire({
        title: label('pb_rollback_confirm_title', 'Rollback this revision?'),
        text: label('pb_rollback_confirm_text', 'Current page layout and SEO metadata will be replaced.'),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: label('updater_action_rollback', 'Rollback'),
        cancelButtonText: label('cancel', 'Cancel'),
        reverseButtons: true,
        buttonsStyling: false,
        customClass: {
            popup: 'pf-swal-popup',
            title: 'pf-swal-title',
            htmlContainer: 'pf-swal-content',
            confirmButton: 'pf-swal-confirm',
            cancelButton: 'pf-swal-cancel',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    router.post(props.routes.rollbackBase.replace('/0/rollback', `/${revisionId}/rollback`), {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(label('pb_rollback_completed', 'Rollback completed.'));
        },
        onError: () => {
            toast.error(label('pb_rollback_failed', 'Rollback failed. Please try again.'));
        },
    });
};

const compare = (event) => {
    const formData = new FormData(event.target);
    router.get(props.routes.compare, {
        left_revision_id: formData.get('left_revision_id'),
        right_revision_id: formData.get('right_revision_id'),
    });
};

const actionTone = (action) => {
    if (action === 'published') {
        return 'success';
    }

    if (action === 'rollback') {
        return 'warning';
    }

    return 'info';
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <UiPageHeader :title="`${label('pb_revisions_for', 'Revisions for')} ${page.slug}`" :subtitle="label('pb_revisions_subtitle', 'Compare snapshots and rollback quickly.')" />

            <UiCard class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ label('pb_timeline', 'Timeline') }}</h2>
                <ul class="mt-2 space-y-2 text-sm text-slate-700">
                    <li v-for="revision in revisions" :key="revision.id" class="flex flex-wrap items-center gap-2 rounded border border-slate-200 px-3 py-2">
                        <span class="font-medium text-slate-900">#{{ revision.revision_no }}</span>
                        <UiStatusBadge :tone="actionTone(revision.action)">{{ revision.action }}</UiStatusBadge>
                        <span class="text-slate-600">{{ revision.created_at }}</span>
                        <UiButton type="button" tone="outline" size="xs" radius="lg" @click="rollback(revision.id)">{{ label('updater_action_rollback', 'Rollback') }}</UiButton>
                    </li>
                    <li v-if="revisions.length === 0" class="text-slate-500">{{ label('pb_no_revisions', 'No revisions yet.') }}</li>
                </ul>
            </UiCard>

            <UiCard class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ label('pb_compare_revisions', 'Compare revisions') }}</h2>
                <form class="mt-2 grid gap-2 md:grid-cols-2" @submit.prevent="compare">
                    <UiField :label="label('pb_left_revision', 'Left revision')">
                        <select name="left_revision_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                            <option v-for="revision in revisions" :key="`l-${revision.id}`" :value="revision.id" :selected="leftRevision?.id === revision.id">#{{ revision.revision_no }} - {{ revision.action }}</option>
                        </select>
                    </UiField>
                    <UiField :label="label('pb_right_revision', 'Right revision')">
                        <select name="right_revision_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                            <option v-for="revision in revisions" :key="`r-${revision.id}`" :value="revision.id" :selected="rightRevision?.id === revision.id">#{{ revision.revision_no }} - {{ revision.action }}</option>
                        </select>
                    </UiField>
                    <div class="md:col-span-2">
                        <UiButton type="submit" tone="neutral" radius="lg">{{ label('pb_compare', 'Compare') }}</UiButton>
                    </div>
                </form>

                <ul v-if="diff.changed" class="mt-3 space-y-2 text-sm text-slate-700">
                    <li v-for="change in (diff.changes ?? [])" :key="change.key" class="rounded border border-slate-200 px-3 py-2">
                        <strong class="text-slate-900">{{ change.key }}</strong>
                        <div class="mt-1"><span class="font-medium">{{ label('pb_before', 'Before:') }}</span> {{ typeof change.before === 'object' ? JSON.stringify(change.before) : change.before }}</div>
                        <div><span class="font-medium">{{ label('pb_after', 'After:') }}</span> {{ typeof change.after === 'object' ? JSON.stringify(change.after) : change.after }}</div>
                    </li>
                </ul>
                <UiAlert v-else tone="info">{{ label('pb_no_diff', 'No differences to show.') }}</UiAlert>
            </UiCard>

            <div>
                <UiButton tag="a" :href="routes.pageEdit" tone="outline" radius="lg">{{ label('pb_back_to_editor', 'Back to page editor') }}</UiButton>
            </div>
        </div>
    </AdminLayout>
</template>
