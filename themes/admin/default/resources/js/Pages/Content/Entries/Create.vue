<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';

const props = defineProps({
    contentType: {
        type: Object,
        required: true,
    },
    formFields: {
        type: Array,
        default: () => [],
    },
    defaultStatus: {
        type: String,
        default: 'draft',
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const initialData = props.formFields.reduce((carry, field) => {
    carry[field.key] = field.field_type === 'repeater' ? '[]' : '';
    return carry;
}, {});

const form = useForm({
    slug: '',
    status: props.defaultStatus,
    data: initialData,
});

const submit = () => {
    form.post(props.routes.store);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Create entry for {{ contentType.name }}</h1>
            </div>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <label class="block text-sm">
                    Entry slug
                    <input v-model="form.slug" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</p>
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

                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">Save</button>
                    <a :href="routes.index" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
