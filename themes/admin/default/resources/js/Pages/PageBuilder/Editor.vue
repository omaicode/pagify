<script setup>
import { ref, computed, onBeforeUnmount, onMounted, } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AdminLayout from '@admin-theme/Layouts/AdminLayout.vue';
import UiPageHeader from '@admin-theme/Components/UI/UiPageHeader.vue';
import PageBuilderIframeEditor from '@admin-theme/Components/PageBuilderIframeEditor.vue';

const props = defineProps({
    editor: { type: Object, default: () => ({}) },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;
const isFullscreenActive = ref(false);
const fullscreenEnabled = computed(() => typeof document !== 'undefined' && typeof document.fullscreenEnabled === 'boolean' && document.fullscreenEnabled);
const editorRef = ref(null);

const layout = ref({
    type: 'webstudio',
    webstudio: {
        html: '',
        css: '',
    },
});

const toggleFullscreen = async () => {

    if (editorRef.value === null) {
        return;
    }

    const target = editorRef.value.$el;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (document.fullscreenElement === target) {
        await document.exitFullscreen();
        return;
    }

    await target.requestFullscreen();
};

const syncFullscreenState = () => {
    if (typeof document === 'undefined') {
        isFullscreenActive.value = false;

        return;
    }

    isFullscreenActive.value = document.fullscreenElement === editorRef.value.$el;
};

onMounted(() => {
    if (typeof document !== 'undefined') {
        document.addEventListener('fullscreenchange', syncFullscreenState);
    }
});

onBeforeUnmount(() => {
    if (typeof document !== 'undefined') {
        document.removeEventListener('fullscreenchange', syncFullscreenState);
    }
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 pb-8">
            <UiPageHeader
                :title="label('pb_pages_title', 'Pages')"
                :subtitle="label('pb_pages_subtitle', 'Manage visual pages and publishing state.')"
            >
                <template #actions>
                    <button
                        type="button"
                        class="inline-flex items-center rounded border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        :disabled="!editorRef"
                        @click="toggleFullscreen"
                    >
                        <span v-if="!isFullscreenActive">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M3 21v-5h2v3h3v2zm13 0v-2h3v-3h2v5zM3 8V3h5v2H5v3zm16 0V5h-3V3h5v5z"/></svg>
                        </span>
                        <span v-else>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"><path fill="currentColor" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </span>
                    </button>
                </template>
            </UiPageHeader>

            <PageBuilderIframeEditor
                ref="editorRef"
                v-model="layout"
                :iframe="editor.iframe ?? {}"
                :breakpoints="editor.breakpoints ?? []"
                :canvas-styles="editor.canvas_styles ?? []"
                :active-theme="editor.active_theme ?? ''"
                :is-fullscreen-active="isFullscreenActive"
                :layouts="editor.layouts ?? []"
            />
        </div>
    </AdminLayout>
</template>
