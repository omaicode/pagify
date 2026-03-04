<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../../../../core/resources/js/Layouts/AdminLayout.vue';

const props = defineProps({
    fieldTypes: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    name: '',
    slug: '',
    description: '',
    is_active: true,
    fields: [
        {
            key: 'title',
            label: 'Title',
            field_type: props.fieldTypes[0] ?? 'text',
            config: '{}',
            validation: '{}',
            conditional: '{}',
            sort_order: 0,
            is_required: true,
            is_localized: false,
        },
    ],
});

const submit = () => {
    form.post(props.routes.store);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">Create content type</h1>
                <p class="text-sm text-slate-600">Create a schema definition with at least one field.</p>
            </div>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <label class="block text-sm">
                    Name
                    <input v-model="form.name" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </label>

                <label class="block text-sm">
                    Slug
                    <input v-model="form.slug" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</p>
                </label>

                <label class="block text-sm">
                    Description
                    <textarea v-model="form.description" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" />
                </label>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox">
                    Active
                </label>

                <fieldset class="rounded border border-slate-200 p-3">
                    <legend class="px-1 text-sm font-medium">First field</legend>

                    <label class="block text-sm">
                        Key
                        <input v-model="form.fields[0].key" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    </label>

                    <label class="mt-2 block text-sm">
                        Label
                        <input v-model="form.fields[0].label" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    </label>

                    <label class="mt-2 block text-sm">
                        Field type
                        <select v-model="form.fields[0].field_type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                            <option v-for="fieldType in fieldTypes" :key="fieldType" :value="fieldType">{{ fieldType }}</option>
                        </select>
                    </label>

                    <label class="mt-2 block text-sm">
                        Config JSON
                        <textarea v-model="form.fields[0].config" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" />
                    </label>

                    <label class="mt-2 block text-sm">
                        Validation JSON
                        <textarea v-model="form.fields[0].validation" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" />
                    </label>

                    <label class="mt-2 block text-sm">
                        Conditional JSON
                        <textarea v-model="form.fields[0].conditional" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" />
                    </label>
                </fieldset>

                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">Save</button>
                    <a :href="routes.index" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
