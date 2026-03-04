<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import ContentSchemaBuilder from '@admin-theme/Components/ContentSchemaBuilder.vue';

const props = defineProps({
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
            config: {},
            validation: {},
            conditional: {},
            sort_order: 0,
            is_required: true,
            is_localized: false,
        },
    ],
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});

const submit = () => {
    form.post(props.routes.store);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <div>
                <h1 class="text-xl font-semibold text-slate-900">{{ t.create_content_type ?? 'Create content type' }}</h1>
                <p class="text-sm text-slate-600">{{ t.content_schema_create_subtitle ?? 'Build schema visually with drag-drop. Save will queue migration execution.' }}</p>
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

                <div class="flex gap-2">
                    <button type="submit" class="rounded bg-slate-900 px-3 py-2 text-sm text-white" :disabled="form.processing">{{ t.save_and_queue_migration ?? 'Save and queue migration' }}</button>
                    <a :href="routes.index" class="rounded border border-slate-300 px-3 py-2 text-sm text-slate-700">{{ t.back ?? 'Back' }}</a>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
