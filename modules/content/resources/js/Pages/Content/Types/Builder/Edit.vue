<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '../../../../../../../core/resources/js/Layouts/AdminLayout.vue';

const props = defineProps({
    contentType: { type: Object, required: true },
    fieldTypes: { type: Array, default: () => [] },
    initialFields: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const ensureFields = (fields) => {
    if ((fields ?? []).length > 0) {
        return fields;
    }

    return [{
        key: 'title',
        label: 'Title',
        field_type: 'text',
        config: '{}',
        validation: '{}',
        conditional: '{}',
        sort_order: 0,
        is_required: true,
        is_localized: false,
    }];
};

const toField = (field, index) => ({
    key: field.key ?? '',
    label: field.label ?? '',
    field_type: field.field_type ?? 'text',
    config: JSON.stringify(field.config ?? {}),
    validation: JSON.stringify(field.validation ?? {}),
    conditional: JSON.stringify(field.conditional ?? {}),
    sort_order: index,
    is_required: !!field.is_required,
    is_localized: !!field.is_localized,
});

const form = useForm({
    fields: ensureFields(props.initialFields).map(toField),
});

const addField = () => {
    form.fields.push({
        key: `field_${form.fields.length + 1}`,
        label: `Field ${form.fields.length + 1}`,
        field_type: 'text',
        config: '{}',
        validation: '{}',
        conditional: '{}',
        sort_order: form.fields.length,
        is_required: false,
        is_localized: false,
    });
};

const move = (from, to) => {
    if (to < 0 || to >= form.fields.length) {
        return;
    }

    const [item] = form.fields.splice(from, 1);
    form.fields.splice(to, 0, item);
    form.fields = form.fields.map((field, index) => ({ ...field, sort_order: index }));
};

const removeField = (index) => {
    form.fields.splice(index, 1);
    if (form.fields.length === 0) {
        addField();
    }

    form.fields = form.fields.map((field, order) => ({ ...field, sort_order: order }));
};

const submit = () => form.put(props.routes.update);
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-semibold text-slate-900">Schema builder for {{ contentType.name }}</h1>
            <p class="text-sm text-slate-600">This builder stores schema metadata and queues migration planning jobs.</p>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <section v-for="(field, index) in form.fields" :key="`${field.key}-${index}`" class="rounded border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-900">Field #{{ index + 1 }}</p>

                    <div class="mt-2 grid gap-2 md:grid-cols-2">
                        <label class="text-sm">Key<input v-model="field.key" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required></label>
                        <label class="text-sm">Label<input v-model="field.label" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required></label>
                        <label class="text-sm md:col-span-2">Type
                            <select v-model="field.field_type" class="mt-1 w-full rounded border border-slate-300 px-2 py-1">
                                <option v-for="type in fieldTypes" :key="type" :value="type">{{ type }}</option>
                            </select>
                        </label>
                        <label class="text-sm md:col-span-2">Config JSON<textarea v-model="field.config" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" /></label>
                        <label class="text-sm md:col-span-2">Validation JSON<textarea v-model="field.validation" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" /></label>
                        <label class="text-sm md:col-span-2">Conditional JSON<textarea v-model="field.conditional" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" /></label>
                    </div>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <label class="inline-flex items-center gap-2 text-sm"><input v-model="field.is_required" type="checkbox">Required</label>
                        <label class="inline-flex items-center gap-2 text-sm"><input v-model="field.is_localized" type="checkbox">Localized</label>
                        <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs" :disabled="index === 0" @click="move(index, index - 1)">Move up</button>
                        <button type="button" class="rounded border border-slate-300 px-2 py-1 text-xs" :disabled="index === form.fields.length - 1" @click="move(index, index + 1)">Move down</button>
                        <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="removeField(index)">Remove</button>
                    </div>
                </section>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded border border-slate-300 px-3 py-2 text-sm" @click="addField">Add field</button>
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">Save schema and queue plan</button>
                    <a :href="routes.status" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">View migration plans</a>
                    <a :href="routes.typeEdit" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back to content type edit</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
