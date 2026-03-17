<script setup>
import { useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import PageBuilderIframeEditor from '@admin-theme/Components/PageBuilderIframeEditor.vue';
import UiAlert from '@admin-theme/Components/UI/UiAlert.vue';
import UiButton from '@admin-theme/Components/UI/UiButton.vue';
import UiCard from '@admin-theme/Components/UI/UiCard.vue';
import UiField from '@admin-theme/Components/UI/UiField.vue';
import UiInput from '@admin-theme/Components/UI/UiInput.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import UiSwitch from '@admin-theme/Components/UI/UiSwitch.vue';
import { PAGE_BUILDER_HOST_EVENTS } from '@admin-theme/PageBuilder/iframeMessageContract';
import { toast } from 'vue3-toastify';

const props = defineProps({
    editor: { type: Object, default: () => ({}) },
    startup: { type: Object, default: () => ({}) },
    routes: { type: Object, default: () => ({}) },
});

const form = useForm({
    title: '',
    slug: '',
    status: 'draft',
    layout: {
        ...(props.startup.layout ?? {}),
        type: props.startup.layout?.type ?? 'webstudio',
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
const editorSearchTerm = ref('');
const editorState = ref('synced');
const editorLastSyncedAt = ref('');
const LOCAL_LAYOUT_DRAFT_KEY = 'pagify:page-builder:create:layout-draft';
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

const editorStateLabel = computed(() => {
    if (editorState.value === 'syncing') {
        return label('pb_editor_state_syncing', 'Syncing changes...');
    }

    if (editorState.value === 'dirty') {
        return label('pb_editor_state_unsaved', 'Unsaved changes');
    }

    if (editorLastSyncedAt.value !== '') {
        return `${label('pb_editor_state_synced', 'Changes synced')} · ${editorLastSyncedAt.value}`;
    }

    return label('pb_editor_state_synced', 'Changes synced');
});

const editorStateClass = computed(() => {
    if (editorState.value === 'syncing') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (editorState.value === 'dirty') {
        return 'border-sky-200 bg-sky-50 text-sky-700';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
});

const handleEditorStateChange = (payload) => {
    editorState.value = payload?.state ?? 'synced';
    editorLastSyncedAt.value = payload?.syncedAt ?? '';
};

const pushEditorSearch = () => {
    window.dispatchEvent(new CustomEvent(PAGE_BUILDER_HOST_EVENTS.SEARCH_REQUEST, {
        detail: {
            term: editorSearchTerm.value,
        },
    }));
};

const restoreLocalLayoutDraft = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const raw = window.localStorage.getItem(LOCAL_LAYOUT_DRAFT_KEY);

        if (!raw) {
            return;
        }

        const parsed = JSON.parse(raw);

        if (!parsed || typeof parsed !== 'object') {
            return;
        }

        form.layout = {
            ...form.layout,
            ...parsed,
            theme_layout: parsed.theme_layout ?? form.layout.theme_layout,
        };
    } catch (_) {
        // No-op: keep the initial payload if local draft is malformed.
    }
};

const persistLocalLayoutDraft = (layoutValue) => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(LOCAL_LAYOUT_DRAFT_KEY, JSON.stringify(layoutValue ?? {}));
    } catch (_) {
        // No-op: localStorage may be unavailable in restricted browser contexts.
    }
};

const clearLocalLayoutDraft = () => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.removeItem(LOCAL_LAYOUT_DRAFT_KEY);
};

const submit = () => {
    window.dispatchEvent(new CustomEvent(PAGE_BUILDER_HOST_EVENTS.FLUSH_REQUEST));

    window.setTimeout(() => {
        form.post(props.routes.store, {
            onSuccess: () => {
                clearLocalLayoutDraft();
                toast.success(label('pb_page_created', 'Page created.'));
            },
            onError: () => {
                toast.error(label('pb_create_failed', 'Create failed. Please check required fields.'));
            },
        });
    }, 0);
};

onMounted(() => {
    restoreLocalLayoutDraft();
});

watch(() => form.layout, (nextLayout) => {
    persistLocalLayoutDraft(nextLayout);
}, { deep: true });
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 pb-28 md:pb-24">
            <UiPageHeader :title="label('pb_create_page', 'Create page')" :subtitle="label('pb_create_subtitle', 'Build and publish page content visually.')" />

            <div class="grid gap-4 lg:grid-cols-12">
                <UiCard class="space-y-3 lg:col-span-6">
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

                <UiCard class="space-y-3 lg:col-span-6">
                    <p class="text-sm font-semibold text-slate-800">{{ label('pb_editor_quick_guide_title', 'Quick start guide') }}</p>
                    <ol class="list-decimal space-y-1 pl-5 text-xs text-slate-600">
                        <li>{{ label('pb_editor_quick_guide_step_1', 'Pick a block from Theme/Quick blocks on the right panel.') }}</li>
                        <li>{{ label('pb_editor_quick_guide_step_2', 'Drop it into the highlighted content area.') }}</li>
                        <li>{{ label('pb_editor_quick_guide_step_3', 'Use Save changes when sync status turns green.') }}</li>
                    </ol>

                    <UiField :label="label('pb_block_search_placeholder', 'Search blocks...')">
                        <UiInput
                            v-model="editorSearchTerm"
                            type="search"
                            :placeholder="label('pb_block_search_placeholder', 'Search blocks...')"
                            @input="pushEditorSearch"
                        />
                    </UiField>

                    <p class="text-xs text-slate-500">{{ label('pb_hotkey_hint', 'Shortcut: Ctrl/Cmd + S to sync changes quickly.') }}</p>
                </UiCard>
            </div>

            <UiAlert v-if="Object.keys(form.errors ?? {}).length > 0" tone="danger">
                <h2 class="text-sm font-semibold">{{ label('pb_cannot_create_yet', 'Cannot create page yet') }}</h2>
                <ul class="mt-2 list-disc pl-6 text-sm text-rose-700">
                    <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                </ul>
            </UiAlert>

            <div>
                <PageBuilderIframeEditor
                    v-model="form.layout"
                    :iframe="editor.iframe ?? {}"
                    :blocks="editor.blocks ?? []"
                    :breakpoints="editor.breakpoints ?? []"
                    :canvas-styles="editor.canvas_styles ?? []"
                    :active-theme="editor.active_theme ?? ''"
                    :layouts="editor.layouts ?? []"
                    :compact-header="true"
                    @editor-state-change="handleEditorStateChange"
                />
            </div>

            <UiCard class="sticky bottom-3 z-30 border border-slate-200 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/85">
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-full border px-2 py-1 text-xs font-medium" :class="editorStateClass">
                        {{ editorStateLabel }}
                    </span>
                    <UiButton type="button" tone="primary" radius="lg" :disabled="form.processing" @click="submit">{{ label('pb_create_page', 'Create page') }}</UiButton>
                    <UiButton tag="a" :href="routes.index" tone="outline" radius="lg">{{ label('pb_back_to_pages', 'Back to pages') }}</UiButton>
                </div>
            </UiCard>
        </div>
    </AdminLayout>
</template>
