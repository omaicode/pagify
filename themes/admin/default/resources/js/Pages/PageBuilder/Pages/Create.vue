<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import PageBuilderEditor from '@admin-theme/Components/PageBuilderEditor.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiInput from '@admin-theme/Components/UI/UiInput.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import UiSwitch from '@admin-theme/Components/UI/UiSwitch.vue';
import { toast } from 'vue3-toastify';

const props = defineProps({
    editor: { type: Object, default: () => ({}) },
    startup: { type: Object, default: () => ({}) },
    templates: { type: Array, default: () => [] },
    routes: { type: Object, default: () => ({}) },
});

const form = useForm({
    title: '',
    slug: '',
    status: 'draft',
    template_slug: props.startup.template_slug ?? '',
    layout: {
        ...(props.startup.layout ?? {}),
        theme_layout: props.startup.layout?.theme_layout ?? props.editor.default_layout ?? '',
    },
    seo_meta: {
        title: '',
        description: '',
        og_image: '',
        canonical_url: '',
        json_ld: {},
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;
const activeTab = ref('basic');
const slugManuallyEdited = ref(false);
const publishEnabled = computed({
    get: () => form.status === 'published',
    set: (value) => {
        form.status = value ? 'published' : 'draft';
    },
});

const slugify = (value) => String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase()
    .trim()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '')
    .replace(/-{2,}/g, '-');

const syncSlugFromTitle = () => {
    if (slugManuallyEdited.value) {
        return;
    }

    form.slug = slugify(form.title);
};

const handleSlugInput = () => {
    slugManuallyEdited.value = form.slug.trim() !== '';

    if (!slugManuallyEdited.value) {
        syncSlugFromTitle();
    }
};

watch(() => form.title, syncSlugFromTitle);

const submit = () => {
    window.dispatchEvent(new CustomEvent('pbx-editor-flush'));

    window.setTimeout(() => {
        form.post(props.routes.store, {
            onSuccess: () => {
                toast.success(label('pb_page_created', 'Page created.'));
            },
            onError: () => {
                toast.error(label('pb_create_failed', 'Create failed. Please check required fields.'));
            },
        });
    }, 0);
};
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 pb-28 md:pb-24">
            <UiPageHeader :title="label('pb_create_page', 'Create page')" :subtitle="label('pb_create_subtitle', 'Build and publish page content visually.')" />

            <UiCard class="space-y-3">
                <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
                    <UiButton type="button" :tone="activeTab === 'basic' ? 'primary' : 'outline'" radius="lg" @click="activeTab = 'basic'">{{ label('pb_tab_basic', 'Basic info') }}</UiButton>
                    <UiButton type="button" :tone="activeTab === 'seo' ? 'primary' : 'outline'" radius="lg" @click="activeTab = 'seo'">{{ label('pb_tab_seo', 'SEO') }}</UiButton>
                </div>

                <div v-if="activeTab === 'basic'" class="grid gap-3 md:grid-cols-2">
                    <UiField :label="label('title', 'Title')">
                        <UiInput v-model="form.title" type="text" required />
                        <span v-if="form.errors.title" class="mt-1 block text-xs text-rose-600">{{ form.errors.title }}</span>
                    </UiField>

                    <UiField :label="label('slug', 'Slug')">
                        <UiInput v-model="form.slug" type="text" required @input="handleSlugInput" />
                        <span v-if="form.errors.slug" class="mt-1 block text-xs text-rose-600">{{ form.errors.slug }}</span>
                    </UiField>

                    <UiField :label="label('pb_theme_layout', 'Theme layout')" class="md:col-span-2">
                        <select v-model="form.layout.theme_layout" class="pf-input">
                            <option v-for="layoutItem in (editor.layouts ?? [])" :key="layoutItem.path" :value="layoutItem.path">{{ layoutItem.label }}</option>
                        </select>
                    </UiField>

                    <UiField :label="label('pb_publish_page', 'Publish page')" class="md:col-span-2">
                        <UiSwitch
                            v-model="publishEnabled"
                            :true-label="label('published', 'Published')"
                            :false-label="label('draft', 'Draft')"
                            :disabled="form.processing"
                        />
                    </UiField>
                </div>

                <div v-else class="grid gap-3 md:grid-cols-2">
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

            <UiAlert v-if="Object.keys(form.errors ?? {}).length > 0" tone="danger">
                <h2 class="text-sm font-semibold">{{ label('pb_cannot_create_yet', 'Cannot create page yet') }}</h2>
                <ul class="mt-2 list-disc pl-6 text-sm text-rose-700">
                    <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                </ul>
            </UiAlert>

            <PageBuilderEditor
                v-model="form.layout"
                :blocks="editor.blocks ?? []"
                :breakpoints="editor.breakpoints ?? []"
                :simple-mode="editor.simple_mode ?? true"
                :primary-block-keys="editor.primary_block_keys ?? []"
                :canvas-styles="editor.canvas_styles ?? []"
                :active-theme="editor.active_theme ?? ''"
                :layouts="editor.layouts ?? []"
            />

            <UiCard class="sticky bottom-3 z-30 border border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
                <div class="flex flex-wrap gap-2">
                    <UiButton type="button" tone="primary" radius="lg" :disabled="form.processing" @click="submit">{{ label('pb_create_page', 'Create page') }}</UiButton>
                    <UiButton tag="a" :href="routes.index" tone="outline" radius="lg">{{ label('pb_back_to_pages', 'Back to pages') }}</UiButton>
                </div>
            </UiCard>
        </div>
    </AdminLayout>
</template>
