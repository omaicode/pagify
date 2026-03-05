<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({ sections: [] }),
    },
    blocks: {
        type: Array,
        default: () => [],
    },
    breakpoints: {
        type: Array,
        default: () => ['desktop', 'tablet', 'mobile'],
    },
    reusableSections: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const editorContainer = ref(null);
const selectedReusableSection = ref('');
const isEditorLoading = ref(true);
const editorLoadError = ref('');

let editor = null;
let syncTimeout = null;
let unmounted = false;

const layout = computed(() => props.modelValue ?? {});

const scheduleSync = () => {
    if (!editor) {
        return;
    }

    if (syncTimeout !== null) {
        window.clearTimeout(syncTimeout);
    }

    syncTimeout = window.setTimeout(() => {
        if (!editor) {
            return;
        }

        emit('update:modelValue', {
            type: 'grapesjs',
            grapes: {
                html: editor.getHtml(),
                css: editor.getCss(),
                projectData: editor.getProjectData(),
                updated_at: new Date().toISOString(),
            },
        });
    }, 250);
};

const loadLayoutToEditor = () => {
    if (!editor) {
        return;
    }

    const grapesPayload = (layout.value?.grapes ?? {});
    const projectData = grapesPayload?.projectData;

    if (projectData && typeof projectData === 'object') {
        editor.loadProjectData(projectData);
        return;
    }

    const html = typeof grapesPayload?.html === 'string' ? grapesPayload.html : '';
    const css = typeof grapesPayload?.css === 'string' ? grapesPayload.css : '';

    if (html !== '') {
        editor.setComponents(html);
    }

    if (css !== '') {
        editor.setStyle(css);
    }
};

const resolveDeviceWidth = (breakpoint) => {
    if (breakpoint === 'desktop') {
        return '';
    }

    if (breakpoint === 'tablet') {
        return '768px';
    }

    if (breakpoint === 'mobile') {
        return '375px';
    }

    return '';
};

const mapBlockContent = (block) => {
    const text = block?.props_schema?.text ?? block?.label ?? block?.key ?? label('pb_block', 'Block');

    return {
        type: 'text',
        content: `<section class="pb-block"><h3>${text}</h3><p>${label('pb_edit_block_in_editor', 'Edit this block in GrapesJS.')}</p></section>`,
    };
};

const insertReusableSection = () => {
    if (!editor || selectedReusableSection.value === '') {
        return;
    }

    const section = props.reusableSections.find((item) => String(item.id) === selectedReusableSection.value);

    if (!section) {
        return;
    }

    const schema = section.schema ?? {};
    const grapesPayload = schema?.grapes ?? null;

    if (grapesPayload?.projectData && typeof grapesPayload.projectData === 'object') {
        const pages = grapesPayload.projectData.pages ?? [];
        const firstPage = Array.isArray(pages) ? pages[0] : null;
        const frames = firstPage?.frames ?? [];
        const firstFrame = Array.isArray(frames) ? frames[0] : null;
        const component = firstFrame?.component ?? null;

        if (component !== null) {
            editor.addComponents(component);
            scheduleSync();
            return;
        }
    }

    if (typeof grapesPayload?.html === 'string' && grapesPayload.html.trim() !== '') {
        editor.addComponents(grapesPayload.html);
        scheduleSync();
        return;
    }

    if (Array.isArray(schema?.blocks)) {
        const html = schema.blocks
            .map((block) => `<section><h3>${block?.type ?? 'block'}</h3><p>${block?.props?.text ?? ''}</p></section>`)
            .join('');

        if (html !== '') {
            editor.addComponents(html);
            scheduleSync();
        }
    }
};

onMounted(async () => {
    if (editorContainer.value === null) {
        isEditorLoading.value = false;
        return;
    }

    isEditorLoading.value = true;
    editorLoadError.value = '';

    try {
        const [{ default: grapesjs }] = await Promise.all([
            import('grapesjs'),
            import('grapesjs/dist/css/grapes.min.css'),
        ]);

        if (unmounted || editorContainer.value === null) {
            return;
        }

        editor = grapesjs.init({
            container: editorContainer.value,
            height: '70vh',
            storageManager: false,
            fromElement: false,
            selectorManager: { componentFirst: true },
            deviceManager: {
                devices: props.breakpoints.map((breakpoint) => ({
                    id: breakpoint,
                    name: breakpoint,
                    width: resolveDeviceWidth(breakpoint),
                })),
            },
        });

        props.blocks.forEach((block) => {
            editor.BlockManager.add(`pagify-${block.key}`, {
                label: block.label,
                category: block.source === 'plugin' ? label('pb_plugin_blocks', 'Plugin Blocks') : label('pb_core_blocks', 'Core Blocks'),
                content: mapBlockContent(block),
            });
        });

        loadLayoutToEditor();
        editor.on('update', scheduleSync);
    } catch (_) {
        editorLoadError.value = label('pb_editor_load_failed', 'Unable to load GrapesJS editor. Please refresh and try again.');
    } finally {
        isEditorLoading.value = false;
    }
});

onBeforeUnmount(() => {
    unmounted = true;

    if (syncTimeout !== null) {
        window.clearTimeout(syncTimeout);
    }

    if (editor) {
        editor.destroy();
        editor = null;
    }
});
</script>

<template>
    <div class="space-y-3">
        <div class="rounded border border-slate-200 bg-white p-3">
            <p class="text-xs text-slate-500">{{ label('pb_editor_hint', 'GrapesJS canvas is active. Use the left block manager and top device switcher for responsive editing.') }}</p>
        </div>

        <div v-if="isEditorLoading" class="rounded border border-slate-200 bg-white p-3 text-sm text-slate-600">
            {{ label('pb_loading_editor', 'Loading GrapesJS editor...') }}
        </div>

        <div v-if="editorLoadError !== ''" class="rounded border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            {{ editorLoadError }}
        </div>

        <div class="rounded border border-slate-200 bg-white p-3">
            <label class="block text-xs font-medium text-slate-700">{{ label('pb_insert_reusable_section', 'Insert reusable section') }}</label>
            <div class="mt-2 flex flex-wrap gap-2">
                <select v-model="selectedReusableSection" class="min-w-64 rounded border border-slate-300 px-2 py-1 text-sm">
                    <option value="">{{ label('pb_choose_section', 'Choose section') }}</option>
                    <option v-for="sectionTemplate in reusableSections" :key="sectionTemplate.id" :value="String(sectionTemplate.id)">
                        {{ sectionTemplate.name }}
                    </option>
                </select>
                <button type="button" class="rounded border border-slate-300 px-3 py-1 text-sm" @click="insertReusableSection">{{ label('pb_insert', 'Insert') }}</button>
            </div>
        </div>

        <div v-show="!isEditorLoading && editorLoadError === ''" ref="editorContainer" class="gjs-wrapper overflow-hidden rounded border border-slate-200" />
    </div>
</template>
