<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import {
    PAGE_BUILDER_HOST_EVENTS,
    PAGE_BUILDER_IFRAME_CHILD_TO_PARENT,
    PAGE_BUILDER_IFRAME_NAMESPACE,
    PAGE_BUILDER_IFRAME_PARENT_TO_CHILD,
    PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
} from '../PageBuilder/iframeMessageContract';

const props = defineProps({
    modelValue: {
        type: Object,
        default: () => ({}),
    },
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

const emit = defineEmits(['update:modelValue', 'editor-state-change']);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const iframeRef = ref(null);
const isReady = ref(false);
const isLoading = ref(true);
const loadError = ref('');
const syncingState = ref('syncing');
const applyingRemoteLayout = ref(false);
let readyTimeoutId = null;

const iframeEnabled = computed(() => Boolean(props.iframe?.enabled));
const iframeSrc = computed(() => String(props.iframe?.url ?? ''));
const iframeOrigin = computed(() => {
    const configured = String(props.iframe?.origin ?? '').trim();

    if (configured !== '') {
        return configured;
    }

    try {
        return new URL(iframeSrc.value).origin;
    } catch (_) {
        return '';
    }
});

const messageNamespace = computed(() => String(props.iframe?.message_namespace ?? PAGE_BUILDER_IFRAME_NAMESPACE));
const tokenRefreshUrl = computed(() => String(props.iframe?.token_refresh_url ?? ''));
const tokenVerifyUrl = computed(() => String(props.iframe?.token_verify_url ?? ''));
const contractUrl = computed(() => String(props.iframe?.contract_url ?? ''));

const currentLayout = computed(() => props.modelValue ?? {});

const setState = (state) => {
    syncingState.value = state;

    emit('editor-state-change', {
        state,
        syncedAt: state === 'synced' ? new Date().toLocaleTimeString() : '',
    });
};

const postToIframe = (type, payload = {}) => {
    const frame = iframeRef.value;

    if (!frame || !frame.contentWindow) {
        return;
    }

    const targetOrigin = iframeOrigin.value || '*';

    frame.contentWindow.postMessage({
        type,
        namespace: messageNamespace.value,
        payload,
    }, targetOrigin);
};

const requestTokenRefresh = async () => {
    if (tokenRefreshUrl.value === '') {
        return null;
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const response = await fetch(tokenRefreshUrl.value, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            ...(csrf !== '' ? { 'X-CSRF-TOKEN': csrf } : {}),
        },
        body: JSON.stringify({
            ...(props.pageId !== null && props.pageId !== '' ? { page_id: Number(props.pageId) } : {}),
            ...(props.activeTheme ? { theme: props.activeTheme } : {}),
        }),
    });

    if (!response.ok) {
        throw new Error(label('pb_editor_token_refresh_failed', 'Failed to refresh editor token.'));
    }

    const json = await response.json();

    if (!json?.success) {
        throw new Error(label('pb_editor_token_refresh_failed', 'Failed to refresh editor token.'));
    }

    return {
        accessToken: String(json?.data?.access_token ?? ''),
        expiresAt: String(json?.data?.expires_at ?? ''),
    };
};

const sendInitPayload = () => {
    postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.INIT, {
        protocolVersion: PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
        accessToken: String(props.iframe?.access_token ?? ''),
        tokenExpiresAt: props.iframe?.token_expires_at ?? null,
        contractUrl: contractUrl.value,
        tokenRefreshUrl: tokenRefreshUrl.value,
        tokenVerifyUrl: tokenVerifyUrl.value,
        layout: currentLayout.value,
        context: {
            activeTheme: props.activeTheme,
            layouts: props.layouts,
            breakpoints: props.breakpoints,
            blocks: props.blocks,
            canvasStyles: props.canvasStyles,
        },
    });
};

const startReadyTimeout = () => {
    if (readyTimeoutId !== null) {
        window.clearTimeout(readyTimeoutId);
    }

    readyTimeoutId = window.setTimeout(() => {
        if (isReady.value) {
            return;
        }

        isLoading.value = false;
        loadError.value = label('pb_editor_iframe_timeout', 'Editor handshake timed out. Please refresh and verify iframe origin/token settings.');
        setState('dirty');
    }, 15000);
};

const handleMessage = (event) => {
    if (iframeOrigin.value !== '' && event.origin !== iframeOrigin.value) {
        return;
    }

    const data = event?.data;

    if (!data || typeof data !== 'object') {
        return;
    }

    if (String(data.namespace ?? '') !== messageNamespace.value) {
        return;
    }

    const messageType = String(data.type ?? '');
    const payload = data.payload ?? {};

    if (messageType === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.READY) {
        isReady.value = true;
        if (readyTimeoutId !== null) {
            window.clearTimeout(readyTimeoutId);
            readyTimeoutId = null;
        }
        isLoading.value = false;
        loadError.value = '';
        setState('synced');
        sendInitPayload();
        return;
    }

    if (messageType === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.ERROR) {
        loadError.value = String(payload?.message ?? label('pb_editor_iframe_error', 'Editor iframe failed to load.'));
        isLoading.value = false;
        setState('dirty');
        return;
    }

    if (messageType === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.LAYOUT_CHANGE) {
        applyingRemoteLayout.value = true;
        emit('update:modelValue', payload?.layout ?? currentLayout.value);
        applyingRemoteLayout.value = false;

        setState(payload?.synced ? 'synced' : 'dirty');

        return;
    }

    if (messageType === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.TOKEN_REFRESH_REQUEST) {
        requestTokenRefresh()
            .then((token) => {
                if (!token || token.accessToken === '') {
                    throw new Error(label('pb_editor_token_refresh_failed', 'Failed to refresh editor token.'));
                }

                postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.TOKEN_REFRESH_RESULT, {
                    ok: true,
                    accessToken: token.accessToken,
                    expiresAt: token.expiresAt,
                });
            })
            .catch((error) => {
                postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.TOKEN_REFRESH_RESULT, {
                    ok: false,
                    message: error instanceof Error ? error.message : label('pb_editor_token_refresh_failed', 'Failed to refresh editor token.'),
                });
            });
    }
};

const handleFlushRequest = () => {
    setState('syncing');
    postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.FLUSH);
};

const handleSearchRequest = (event) => {
    postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.SEARCH, {
        term: String(event?.detail?.term ?? ''),
    });
};

const onFrameLoad = () => {
    if (!iframeEnabled.value) {
        return;
    }

    isLoading.value = true;
    loadError.value = '';
    isReady.value = false;
    startReadyTimeout();
    sendInitPayload();
};

watch(() => currentLayout.value, (nextLayout) => {
    if (!isReady.value || applyingRemoteLayout.value) {
        return;
    }

    postToIframe(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.SET_LAYOUT, {
        layout: nextLayout,
    });
}, { deep: true });

onMounted(() => {
    window.addEventListener('message', handleMessage);
    window.addEventListener(PAGE_BUILDER_HOST_EVENTS.FLUSH_REQUEST, handleFlushRequest);
    window.addEventListener(PAGE_BUILDER_HOST_EVENTS.SEARCH_REQUEST, handleSearchRequest);
    setState('syncing');
    startReadyTimeout();
});

onBeforeUnmount(() => {
    window.removeEventListener('message', handleMessage);
    window.removeEventListener(PAGE_BUILDER_HOST_EVENTS.FLUSH_REQUEST, handleFlushRequest);
    window.removeEventListener(PAGE_BUILDER_HOST_EVENTS.SEARCH_REQUEST, handleSearchRequest);
    if (readyTimeoutId !== null) {
        window.clearTimeout(readyTimeoutId);
        readyTimeoutId = null;
    }
});
</script>

<template>
    <section class="space-y-3">
        <div class="flex flex-wrap items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs text-slate-600">
            <span class="inline-flex items-center rounded-full border border-slate-300 bg-white px-2 py-1 font-medium text-slate-700">{{ label('pb_editor_iframe_mode', 'Iframe editor mode') }}</span>
            <span v-if="isLoading" class="text-slate-500">{{ label('pb_editor_iframe_loading', 'Loading editor...') }}</span>
            <span v-else-if="loadError" class="text-rose-600">{{ loadError }}</span>
            <span v-else class="text-emerald-600">{{ label('pb_editor_iframe_ready', 'Editor connected') }}</span>
        </div>

        <div v-if="!iframeEnabled || iframeSrc === ''" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            {{ label('pb_editor_iframe_not_configured', 'Iframe editor is not configured. Set PAGE_BUILDER_IFRAME_EDITOR_ENABLED and PAGE_BUILDER_IFRAME_EDITOR_URL.') }}
        </div>

        <iframe
            v-else
            ref="iframeRef"
            :src="iframeSrc"
            class="min-h-[70vh] w-full rounded-xl border border-slate-200 bg-white"
            :title="label('pb_editor_iframe_title', 'Page builder editor')"
            loading="eager"
            @load="onFrameLoad"
        />
    </section>
</template>
