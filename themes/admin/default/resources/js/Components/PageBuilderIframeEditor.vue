<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
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
const frameContainerRef = ref(null);
const isFullscreenActive = ref(false);

const iframeEnabled = computed(() => Boolean(props.iframe?.enabled));
const iframeSrc = computed(() => String(props.iframe?.url ?? ''));
const fullscreenEnabled = computed(() => typeof document !== 'undefined' && typeof document.fullscreenEnabled === 'boolean' && document.fullscreenEnabled);
const fullscreenLabel = computed(() => {
    if (fullscreenEnabled.value) {
        return isFullscreenActive.value
            ? label('pb_editor_fullscreen_exit', 'Exit fullscreen')
            : label('pb_editor_fullscreen_enter', 'Fullscreen');
    }

    return label('pb_editor_fullscreen_fallback', 'Open full window');
});

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

const syncFullscreenState = () => {
    if (typeof document === 'undefined') {
        isFullscreenActive.value = false;

        return;
    }

    isFullscreenActive.value = document.fullscreenElement === frameContainerRef.value;
};

const toggleFullscreen = async () => {
    if (iframeSrc.value === '') {
        return;
    }

    if (!fullscreenEnabled.value) {
        window.open(iframeSrc.value, '_blank', 'noopener,noreferrer');

        return;
    }

    const target = frameContainerRef.value;
    if (!(target instanceof HTMLElement)) {
        return;
    }

    if (document.fullscreenElement === target) {
        await document.exitFullscreen();

        return;
    }

    await target.requestFullscreen();
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
    <section class="space-y-3">
        <div class="flex justify-end">
            <button
                type="button"
                class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                :disabled="!iframeEnabled || iframeSrc === ''"
                @click="toggleFullscreen"
            >
                {{ fullscreenLabel }}
            </button>
        </div>

        <div v-if="isLoading" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700">
            {{ label('pb_editor_iframe_loading', 'Loading editor...') }}
        </div>

        <div v-if="loadError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ loadError }}
        </div>

        <div v-if="!iframeEnabled || iframeSrc === ''" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ label('pb_editor_iframe_not_configured', 'Iframe editor is currently unavailable.') }}
        </div>

        <div ref="frameContainerRef" class="rounded-xl bg-white">
            <iframe
                v-if="iframeEnabled && iframeSrc !== ''"
                ref="iframeRef"
                :src="iframeSrc"
                class="min-h-[70vh] w-full rounded-xl border border-slate-200 bg-white"
                :title="label('pb_editor_iframe_title', 'Page builder editor')"
                loading="eager"
                @load="onFrameLoad"
                @error="onFrameError"
            />
        </div>
    </section>
</template>
