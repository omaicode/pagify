<script setup>
import { computed, ref } from 'vue';
import { usePage } from '@inertiajs/vue3';

const props = defineProps({
    iframe: {
        type: Object,
        default: () => ({}),
    },
    layouts: {
        type: Array,
        default: () => [],
    },
    breakpoints: {
        type: Array,
        default: () => ['desktop', 'tablet', 'mobile'],
    },
    blocks: {
        type: Array,
        default: () => [],
    },
    activeTheme: {
        type: String,
        default: '',
    },
    canvasStyles: {
        type: Array,
        default: () => [],
    },
    compactHeader: {
        type: Boolean,
        default: false,
    },
    pageId: {
        type: [Number, String, null],
        default: null,
    },
});

const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const isLoading = ref(true);
const loadError = ref('');

const iframeEnabled = computed(() => Boolean(props.iframe?.enabled));
const iframeSrc = computed(() => String(props.iframe?.url ?? ''));

const onFrameLoad = () => {
    if (!iframeEnabled.value) {
        return;
    }

    isLoading.value = false;
    loadError.value = '';
};

const onFrameError = () => {
    isLoading.value = false;
    loadError.value = label('pb_editor_iframe_error', 'Editor iframe failed to load.');
};
</script>

<template>
    <section class="space-y-3">
        <div v-if="isLoading" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            {{ label('pb_editor_iframe_loading', 'Loading editor...') }}
        </div>

        <div v-if="loadError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ loadError }}
        </div>

        <div v-if="!iframeEnabled || iframeSrc === ''" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ label('pb_editor_iframe_not_configured', 'Iframe editor is currently unavailable.') }}
        </div>

        <iframe
            v-else
            ref="iframeRef"
            :src="iframeSrc"
            class="min-h-[70vh] w-full rounded-xl border border-slate-200 bg-white"
            :title="label('pb_editor_iframe_title', 'Page builder editor')"
            loading="eager"
            @load="onFrameLoad"
            @error="onFrameError"
        />
    </section>
</template>
