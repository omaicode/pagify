<script setup>
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import ContentSchemaBuilder from '@admin-theme/Components/ContentSchemaBuilder.vue';

const props = defineProps({
    contentType: { type: Object, required: true },
    fieldTypes: { type: Array, default: () => [] },
    relationTypes: { type: Array, default: () => [] },
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
        config: {},
        validation: {},
        conditional: {},
        sort_order: 0,
        is_required: true,
        is_localized: false,
    }];
};

const toField = (field, index) => ({
    key: field.key ?? '',
    label: field.label ?? '',
    field_type: field.field_type ?? 'text',
    config: field.config ?? {},
    validation: field.validation ?? {},
    conditional: field.conditional ?? {},
    sort_order: index,
    is_required: !!field.is_required,
    is_localized: !!field.is_localized,
});

const form = useForm({
    fields: ensureFields(props.initialFields).map(toField),
});

const submit = () => form.put(props.routes.update);
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-semibold text-slate-900">Schema builder for {{ contentType.name }}</h1>
            <p class="text-sm text-slate-600">Visual drag-drop builder. Save will queue and execute migration DDL.</p>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <ContentSchemaBuilder
                    v-model="form.fields"
                    :field-types="fieldTypes"
                    :relation-types="relationTypes"
                />

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">Save schema and execute queue migration</button>
                    <a :href="routes.status" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">View migration plans</a>
                    <a :href="routes.typeEdit" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">Back to content type edit</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
