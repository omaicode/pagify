<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

const props = defineProps({
    contentType: { type: Object, required: true },
    entry: { type: Object, required: true },
    formFields: { type: Array, default: () => [] },
    resolvedRelations: { type: Object, default: () => ({}) },
    publishActionsAllowed: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const form = useForm({
    slug: props.entry.slug,
    status: props.entry.status,
    data: { ...(props.entry.data ?? {}) },
});

const scheduleForm = useForm({
    scheduled_publish_at: props.entry.scheduled_publish_at,
    scheduled_unpublish_at: props.entry.scheduled_unpublish_at,
});

const submit = () => form.put(props.routes.update);
const destroyEntry = () => {
    if (window.confirm('Delete this entry?')) {
        form.delete(props.routes.destroy);
    }
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Edit entry {{ entry.slug }} for {{ contentType.name }}</h1>
                <p class="text-sm text-slate-600">
                    Current status: <strong>{{ entry.status }}</strong>
                    <span v-if="entry.published_at"> | Published at: {{ entry.published_at }}</span>
                    <span v-if="entry.scheduled_publish_at"> | Scheduled publish: {{ entry.scheduled_publish_at }}</span>
                    <span v-if="entry.scheduled_unpublish_at"> | Scheduled unpublish: {{ entry.scheduled_unpublish_at }}</span>
                </p>
            </div>

            <section v-if="Object.keys(resolvedRelations ?? {}).length" class="rounded border border-slate-200 bg-white p-4">
                <h2 class="text-sm font-semibold text-slate-900">Resolved relations</h2>
                <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-slate-700">
                    <li v-for="(relations, fieldKey) in resolvedRelations" :key="fieldKey">
                        <strong>{{ fieldKey }}</strong>: {{ (relations ?? []).map((item) => item.target_slug).filter(Boolean).join(', ') }}
                    </li>
                </ul>
            </section>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <label class="block text-sm">
                    Entry slug
                    <input v-model="form.slug" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                </label>

                <label class="block text-sm">
                    Status
                    <select v-model="form.status" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                        <option value="draft">draft</option>
                        <option value="published">published</option>
                        <option value="scheduled">scheduled</option>
                    </select>
                </label>

                <fieldset v-for="field in formFields" :key="field.key" class="rounded border border-slate-200 p-3">
                    <legend class="px-1 text-sm font-medium">{{ field.label }} ({{ field.field_type }})</legend>

                    <input
                        v-if="field.field_type !== 'boolean' && field.field_type !== 'select'"
                        v-model="form.data[field.key]"
                        type="text"
                        class="mt-1 w-full rounded border border-slate-300 px-2 py-1"
                    >

                    <label v-else-if="field.field_type === 'boolean'" class="inline-flex items-center gap-2 text-sm">
                        <input v-model="form.data[field.key]" type="checkbox" true-value="1" false-value="">
                        Enabled
                    </label>

                    <select v-else v-model="form.data[field.key]" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                        <option value="">-- choose --</option>
                        <option v-for="option in (field.config?.options ?? [])" :key="option" :value="option">{{ option }}</option>
                    </select>

                    <p v-if="form.errors[`data.${field.key}`]" class="mt-1 text-xs text-rose-600">{{ form.errors[`data.${field.key}`] }}</p>
                </fieldset>

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">Update</button>
                    <button type="button" class="rounded border border-rose-300 px-3 py-2 text-sm text-rose-700" :disabled="form.processing" @click="destroyEntry">Delete</button>
                    <a :href="routes.revisions" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">View revisions</a>
                    <a :href="routes.index" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back to entries</a>
                </div>
            </form>

            <div class="space-y-2 rounded border border-slate-200 bg-white p-4">
                <p class="text-sm font-semibold text-slate-900">Publishing workflow</p>
                <div class="flex flex-wrap gap-2">
                    <button v-if="publishActionsAllowed.publish" type="button" class="rounded border border-slate-300 px-3 py-2 text-sm" @click="form.post(routes.publish)">Publish now</button>
                    <button v-if="publishActionsAllowed.unpublish" type="button" class="rounded border border-slate-300 px-3 py-2 text-sm" @click="form.post(routes.unpublish)">Move to draft now</button>
                </div>

                <form v-if="publishActionsAllowed.schedule" class="grid gap-2 md:grid-cols-2" @submit.prevent="scheduleForm.post(routes.schedule)">
                    <label class="text-sm">
                        Schedule publish at
                        <input v-model="scheduleForm.scheduled_publish_at" type="datetime-local" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                    </label>
                    <label class="text-sm">
                        Schedule unpublish at
                        <input v-model="scheduleForm.scheduled_unpublish_at" type="datetime-local" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                    </label>
                    <div class="md:col-span-2">
                        <button type="submit" class="rounded border border-slate-300 px-3 py-2 text-sm" :disabled="scheduleForm.processing">Save schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </AdminLayout>
</template>
