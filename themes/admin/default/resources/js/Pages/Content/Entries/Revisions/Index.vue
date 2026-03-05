<script setup>
import { router } from '@inertiajs/vue3';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

const props = defineProps({
    contentType: { type: Object, required: true },
    entry: { type: Object, required: true },
    revisions: { type: Array, default: () => [] },
    leftRevision: { type: Object, default: null },
    rightRevision: { type: Object, default: null },
    diff: { type: Object, default: () => ({ changed: false, changes: [] }) },
    routes: { type: Object, default: () => ({}) },
});

const rollback = async (revisionId) => {
    const result = await Swal.fire({
        title: 'Rollback this revision?',
        text: 'Current entry data will be replaced by selected revision snapshot.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Rollback',
        cancelButtonText: 'Cancel',
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
            toast.success('Rollback completed.');
        },
        onError: () => {
            toast.error('Rollback failed. Please try again.');
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
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-semibold text-slate-900">Revisions for {{ entry.slug }} ({{ contentType.slug }})</h1>

            <section class="rounded border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-slate-900">Timeline</h2>
                <ul class="mt-2 list-disc space-y-2 pl-6 text-sm text-slate-700">
                    <li v-for="revision in revisions" :key="revision.id">
                        #{{ revision.revision_no }} - {{ revision.action }} - {{ revision.created_at }}
                        <button type="button" class="ml-2 rounded border border-slate-300 px-2 py-1 text-xs" @click="rollback(revision.id)">Rollback to this revision</button>
                    </li>
                    <li v-if="revisions.length === 0">No revisions yet.</li>
                </ul>
            </section>

            <section class="rounded border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-slate-900">Diff compare</h2>
                <form class="mt-2 grid gap-2 md:grid-cols-2" @submit.prevent="compare">
                    <label class="text-sm">
                        Left revision
                        <select name="left_revision_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                            <option v-for="revision in revisions" :key="`l-${revision.id}`" :value="revision.id" :selected="leftRevision?.id === revision.id">#{{ revision.revision_no }} - {{ revision.action }}</option>
                        </select>
                    </label>
                    <label class="text-sm">
                        Right revision
                        <select name="right_revision_id" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                            <option v-for="revision in revisions" :key="`r-${revision.id}`" :value="revision.id" :selected="rightRevision?.id === revision.id">#{{ revision.revision_no }} - {{ revision.action }}</option>
                        </select>
                    </label>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded border border-slate-300 px-3 py-2 text-sm">Compare</button>
                    </div>
                </form>

                <ul v-if="diff.changed" class="mt-3 list-disc space-y-2 pl-6 text-sm text-slate-700">
                    <li v-for="change in (diff.changes ?? [])" :key="change.key">
                        <strong>{{ change.key }}</strong>
                        <div>Before: {{ typeof change.before === 'object' ? JSON.stringify(change.before) : change.before }}</div>
                        <div>After: {{ typeof change.after === 'object' ? JSON.stringify(change.after) : change.after }}</div>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm text-slate-600">No differences to show.</p>
            </section>

            <a :href="routes.entryEdit" class="text-sm text-slate-700 underline">Back to entry</a>
        </div>
    </AdminLayout>
</template>
