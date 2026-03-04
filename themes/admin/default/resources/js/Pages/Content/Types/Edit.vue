<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import ContentSchemaBuilder from '@admin-theme/Components/ContentSchemaBuilder.vue';

const props = defineProps({
    contentType: {
        type: Object,
        required: true,
    },
    fieldTypes: {
        type: Array,
        default: () => [],
    },
    relationTypes: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        default: () => ({}),
    },
});

const initialFields = (props.contentType.fields ?? []).length > 0
    ? props.contentType.fields
    : [{
        key: 'title',
        label: 'Title',
        field_type: props.fieldTypes[0] ?? 'text',
        config: {},
        validation: {},
        conditional: {},
        sort_order: 0,
        is_required: true,
        is_localized: false,
    }];

const form = useForm({
    name: props.contentType.name,
    slug: props.contentType.slug,
    description: props.contentType.description ?? '',
    is_active: props.contentType.is_active,
    fields: initialFields.map((field, index) => ({
        key: field.key,
        label: field.label,
        field_type: field.field_type,
        config: field.config ?? {},
        validation: field.validation ?? {},
        conditional: field.conditional ?? {},
        sort_order: field.sort_order ?? index,
        is_required: field.is_required ?? false,
        is_localized: field.is_localized ?? false,
    })),
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const submit = () => {
    form.put(props.routes.update);
};

const destroyType = () => {
    if (!window.confirm(t.value.confirm_delete_content_type ?? 'Delete this content type?')) {
        return;
    }

    form.delete(props.routes.destroy);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ t.edit_content_type ?? 'Edit content type' }}</h1>
                <p class="text-sm text-slate-600">{{ t.content_schema_edit_subtitle ?? 'Visual schema editor. Saving will queue migration execution.' }}</p>
            </div>

            <form class="space-y-3 rounded border border-slate-200 bg-white p-4" @submit.prevent="submit">
                <label class="block text-sm">
                    {{ t.name ?? 'Name' }}
                    <input v-model="form.name" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    <p v-if="form.errors.name" class="mt-1 text-xs text-rose-600">{{ form.errors.name }}</p>
                </label>

                <label class="block text-sm">
                    {{ t.slug ?? 'Slug' }}
                    <input v-model="form.slug" type="text" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" required>
                    <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</p>
                </label>

                <label class="block text-sm">
                    {{ t.description ?? 'Description' }}
                    <textarea v-model="form.description" class="mt-1 w-full rounded border border-slate-300 px-2 py-1" />
                </label>

                <label class="inline-flex items-center gap-2 text-sm">
                    <input v-model="form.is_active" type="checkbox">
                    {{ t.active ?? 'Active' }}
                </label>

                <ContentSchemaBuilder
                    v-model="form.fields"
                    :field-types="fieldTypes"
                    :relation-types="relationTypes"
                />

                <div class="flex flex-wrap gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">{{ t.update_and_queue_migration ?? 'Update and queue migration' }}</button>
                    <button type="button" class="rounded border border-rose-300 px-3 py-2 text-sm text-rose-700" :disabled="form.processing" @click="destroyType">{{ t.delete ?? 'Delete' }}</button>
                    <a :href="routes.entries" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ t.manage_entries ?? 'Manage entries' }}</a>
                    <a :href="routes.builder" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ t.open_schema_builder ?? 'Open schema builder' }}</a>
                    <a :href="routes.builderStatus" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ t.view_migration_plans ?? 'View migration plans' }}</a>
                    <a :href="routes.index" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ t.back ?? 'Back' }}</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
