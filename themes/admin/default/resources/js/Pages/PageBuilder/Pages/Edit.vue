<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import PageBuilderEditor from '@admin-theme/Components/PageBuilderEditor.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiInput from '@admin-theme/Components/UI/UiInput.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import Swal from 'sweetalert2';
import { toast } from 'vue3-toastify';

const props = defineProps({
    page: { type: Object, required: true },
    editor: { type: Object, default: () => ({}) },
    templates: { type: Array, default: () => [] },
    sections: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const form = useForm({
    title: props.page.title,
    slug: props.page.slug,
    status: props.page.status,
    layout: props.page.layout ?? {},
    seo_meta: {
        title: props.page.seo_meta?.title ?? '',
        description: props.page.seo_meta?.description ?? '',
        og_image: props.page.seo_meta?.og_image ?? '',
        canonical_url: props.page.seo_meta?.canonical_url ?? '',
        json_ld: props.page.seo_meta?.json_ld ?? {},
    },
});

const pageContext = usePage();
const t = computed(() => pageContext.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const submit = () => form.put(props.routes.update, {
    onSuccess: () => {
        toast.success(label('pb_page_updated', 'Page updated.'));
    },
    onError: () => {
        toast.error(label('pb_save_failed', 'Save failed. Please check required fields.'));
    },
});

const publish = () => form.post(props.routes.publish, {
    onSuccess: () => {
        toast.success(label('pb_page_published', 'Page published and snapshot generated.'));
    },
    onError: () => {
        toast.error(label('pb_publish_failed', 'Publish failed. Please try again.'));
    },
});

const removePage = async () => {
    const result = await Swal.fire({
        title: label('pb_confirm_delete_title', 'Delete this page?'),
        text: label('pb_confirm_delete_text', `"${props.page.title}" will be permanently removed.`).replace(':title', props.page.title),
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: label('delete', 'Delete'),
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

    form.delete(props.routes.destroy, {
        onSuccess: () => {
            toast.success(label('pb_page_deleted', 'Page deleted.'));
        },
        onError: () => {
            toast.error(label('pb_delete_failed', 'Delete failed. Please try again.'));
        },
    });
};

const saveSectionTemplate = () => {
    if (!form.layout || Object.keys(form.layout).length === 0) {
        return;
    }

    const slug = window.prompt(label('pb_prompt_section_slug', 'Section template slug'));
    const name = window.prompt(label('pb_prompt_section_name', 'Section template name'));

    if (!slug || !name) {
        return;
    }

    useForm({
        name,
        slug,
        schema: {
            grapes: form.layout?.grapes ?? form.layout,
        },
    }).post(props.routes.storeSectionTemplate, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(label('pb_section_template_saved', 'Section template saved.'));
        },
        onError: () => {
            toast.error(label('pb_section_template_save_failed', 'Cannot save section template.'));
        },
    });
};

const savePageTemplate = () => {
    const slug = window.prompt(label('pb_prompt_page_slug', 'Page template slug'));
    const name = window.prompt(label('pb_prompt_page_name', 'Page template name'));

    if (!slug || !name) {
        return;
    }

    useForm({
        name,
        slug,
        category: 'custom',
        description: label('pb_saved_from_visual_editor', 'Saved from visual editor'),
        schema: {
            grapes: form.layout?.grapes ?? form.layout,
        },
    }).post(props.routes.storePageTemplate, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(label('pb_page_template_saved', 'Page template saved.'));
        },
        onError: () => {
            toast.error(label('pb_page_template_save_failed', 'Cannot save page template.'));
        },
    });
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4">
            <UiPageHeader :title="`${label('pb_edit_page', 'Edit page')}: ${page.title}`" :subtitle="`${label('status', 'Status')}: ${page.status}${page.published_at ? ` · ${label('published_at', 'Published at:')} ${page.published_at}` : ''}`" />

            <UiCard class="space-y-3">
                <div class="grid gap-3 md:grid-cols-2">
                    <UiField :label="label('title', 'Title')">
                        <UiInput v-model="form.title" type="text" required />
                        <span v-if="form.errors.title" class="mt-1 block text-xs text-rose-600">{{ form.errors.title }}</span>
                    </UiField>
                    <UiField :label="label('slug', 'Slug')">
                        <UiInput v-model="form.slug" type="text" required />
                        <span v-if="form.errors.slug" class="mt-1 block text-xs text-rose-600">{{ form.errors.slug }}</span>
                    </UiField>
                </div>
            </UiCard>

            <UiAlert v-if="Object.keys(form.errors ?? {}).length > 0" tone="danger">
                <h2 class="text-sm font-semibold">{{ label('pb_cannot_save_yet', 'Cannot save page yet') }}</h2>
                <ul class="mt-2 list-disc pl-6 text-sm text-rose-700">
                    <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                </ul>
            </UiAlert>

            <PageBuilderEditor
                v-model="form.layout"
                :blocks="editor.blocks ?? []"
                :breakpoints="editor.breakpoints ?? []"
                :reusable-sections="sections"
            />

            <UiCard class="space-y-3">
                <h2 class="text-sm font-semibold text-slate-900">{{ label('pb_seo', 'SEO') }}</h2>
                <div class="mt-2 grid gap-3 md:grid-cols-2">
                    <UiField :label="label('pb_seo_title', 'SEO title')">
                        <UiInput v-model="form.seo_meta.title" type="text" />
                        <span v-if="form.errors['seo_meta.title']" class="mt-1 block text-xs text-rose-600">{{ form.errors['seo_meta.title'] }}</span>
                    </UiField>
                    <UiField :label="label('pb_canonical_url', 'Canonical URL')">
                        <UiInput v-model="form.seo_meta.canonical_url" type="url" />
                        <span v-if="form.errors['seo_meta.canonical_url']" class="mt-1 block text-xs text-rose-600">{{ form.errors['seo_meta.canonical_url'] }}</span>
                    </UiField>
                    <UiField :label="label('description', 'Description')" class="md:col-span-2">
                        <UiInput v-model="form.seo_meta.description" as="textarea" :rows="2" />
                        <span v-if="form.errors['seo_meta.description']" class="mt-1 block text-xs text-rose-600">{{ form.errors['seo_meta.description'] }}</span>
                    </UiField>
                    <UiField :label="label('pb_og_image_url', 'OG image URL')" class="md:col-span-2">
                        <UiInput v-model="form.seo_meta.og_image" type="text" />
                        <span v-if="form.errors['seo_meta.og_image']" class="mt-1 block text-xs text-rose-600">{{ form.errors['seo_meta.og_image'] }}</span>
                    </UiField>
                </div>
            </UiCard>

            <div class="flex flex-wrap gap-2">
                <UiButton type="button" tone="primary" radius="lg" :disabled="form.processing" @click="submit">{{ label('pb_save_changes', 'Save changes') }}</UiButton>
                <UiButton type="button" tone="neutral" radius="lg" :disabled="form.processing" @click="publish">{{ label('pb_publish_generate_snapshot', 'Publish & generate snapshot') }}</UiButton>
                <UiButton type="button" tone="neutral" radius="lg" :disabled="form.processing" @click="saveSectionTemplate">{{ label('pb_save_first_section', 'Save first section as reusable') }}</UiButton>
                <UiButton type="button" tone="neutral" radius="lg" :disabled="form.processing" @click="savePageTemplate">{{ label('pb_save_as_page_template', 'Save as page template') }}</UiButton>
                <UiButton tag="a" :href="routes.revisions" tone="outline" radius="lg">{{ label('view_revisions', 'View revisions') }}</UiButton>
                <UiButton type="button" tone="danger" radius="lg" :disabled="form.processing" @click="removePage">{{ label('delete', 'Delete') }}</UiButton>
                <UiButton tag="a" :href="routes.index" tone="outline" radius="lg">{{ label('back', 'Back') }}</UiButton>
            </div>
        </div>
    </AdminLayout>
</template>
