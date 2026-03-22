<script setup>
import { ref, computed } from 'vue';
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

const layout = ref({
    type: 'webstudio',
    webstudio: {
        html: '',
        css: '',
    },
});
</script>

<template>
    <AdminLayout>
        <div class="space-y-4 pb-8">
            <UiPageHeader
                :title="label('pb_pages_title', 'Pages')"
                :subtitle="label('pb_pages_subtitle', 'Manage visual pages and publishing state.')"
            />

            <PageBuilderIframeEditor
                v-model="layout"
                :iframe="editor.iframe ?? {}"
                :breakpoints="editor.breakpoints ?? []"
                :canvas-styles="editor.canvas_styles ?? []"
                :active-theme="editor.active_theme ?? ''"
                :layouts="editor.layouts ?? []"
            />
        </div>
    </AdminLayout>
</template>
