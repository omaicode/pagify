import {
    PAGE_BUILDER_HOST_EVENTS,
    PAGE_BUILDER_IFRAME_CHILD_TO_PARENT,
    PAGE_BUILDER_IFRAME_NAMESPACE,
    PAGE_BUILDER_IFRAME_PARENT_TO_CHILD,
    PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
} from './PageBuilder/iframeMessageContract';
import { loadBuilderData } from './editor-host/builder-data-adapter';
import { attachAssetToLayout } from './editor-host/layout-media-adapter';
import {
    appendWebstudioBlock,
    applyWebstudioMarkup,
    normalizeLayout,
    parseTextareaLayout,
} from './editor-host/layout-state-adapter';
import { loadMediaAssets, uploadMediaAsset } from './editor-host/media-adapter';
import { createOriginGuard } from './editor-host/origin-guard';
import { createPostMessageBridge } from './editor-host/postmessage-bridge';
import { renderEditorHostReactApp } from './editor-host/react-app';
import { createTelemetryCollector } from './editor-host/telemetry-hooks';
import { verifyEditorToken } from './editor-host/token-adapter';
import { createWebstudioAdapter } from './editor-host/webstudio-adapter';
import '../webstudio/apps/builder/app/builder/builder.css';

window.PagifyPageBuilderEditorContract = {
    protocolVersion: PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
    namespace: PAGE_BUILDER_IFRAME_NAMESPACE,
    parentToChild: PAGE_BUILDER_IFRAME_PARENT_TO_CHILD,
    childToParent: PAGE_BUILDER_IFRAME_CHILD_TO_PARENT,
    hostEvents: PAGE_BUILDER_HOST_EVENTS,
};

const boot = window.PagifyPageBuilderEditorBoot ?? {};
const namespace = PAGE_BUILDER_IFRAME_NAMESPACE;
const root = document.getElementById('pbx-editor-host-root');
const allowedParentOrigin = String(boot.parentOrigin ?? '').trim();
const builderDataUrl = String(boot.builderDataUrl ?? '').trim();
const mediaAssetsUrl = String(boot.mediaAssetsUrl ?? '').trim();
const mediaUploadUrl = String(boot.mediaUploadUrl ?? '').trim();
const upstreamBuilderUrl = String(boot.upstreamBuilderUrl ?? '').trim();
const runtimeMode = String(boot.runtimeMode ?? 'upstream-embedded').trim();
const accessToken = String(boot.accessToken ?? '');

let layout = {};
let bridge = null;
let webstudioMountNode = null;
let mediaState = {
    query: '',
    status: 'Media idle',
    assets: [],
};
let webstudioState = {
    runtimeMode: String(boot.runtimeMode ?? 'dom-preview'),
    capabilities: {
        domPreview: true,
        upstreamMount: String(boot.runtimeMode ?? 'dom-preview') === 'upstream-experimental',
        mediaBridge: true,
        postMessageBridge: true,
    },
    mounted: false,
    status: 'idle',
    message: 'Adapter idle.',
    warnings: [],
};

const setRootMessage = (message, tone = 'info') => {
    if (!root) {
        return;
    }

    const color = tone === 'error' ? '#b91c1c' : '#0f172a';
    root.innerHTML = `<section style="font-family: ui-sans-serif, system-ui, sans-serif; padding: 16px; color: ${color};">${message}</section>`;
};

const originGuard = createOriginGuard(namespace, allowedParentOrigin);
const telemetry = createTelemetryCollector((type, payload) => {
    originGuard.postToParent(type, payload);
});

const mountUpstreamEmbeddedRuntime = () => {
    if (!root) {
        return false;
    }

    if (upstreamBuilderUrl === '') {
        setRootMessage('Upstream Webstudio URL is missing. Set PAGE_BUILDER_IFRAME_EDITOR_UPSTREAM_URL in environment config.', 'error');
        return true;
    }

    let url;

    try {
        url = new URL(upstreamBuilderUrl);
    } catch (_) {
        setRootMessage('Invalid upstream Webstudio URL configuration.', 'error');
        return true;
    }

    const params = url.searchParams;
    params.set('pagify_token', accessToken);
    params.set('pagify_mode', String(boot.mode ?? 'page-builder'));
    params.set('pagify_theme', String(boot.theme ?? ''));
    params.set('pagify_page_id', String(boot.pageId ?? ''));
    params.set('pagify_site_id', String(boot.siteId ?? ''));
    params.set('pagify_parent_origin', String(boot.parentOrigin ?? ''));
    params.set('pagify_contract_url', String(boot.contractUrl ?? ''));
    params.set('pagify_token_verify_url', String(boot.tokenVerifyUrl ?? ''));
    params.set('pagify_builder_data_url', builderDataUrl);
    params.set('pagify_media_assets_url', mediaAssetsUrl);
    params.set('pagify_media_upload_url', mediaUploadUrl);

    root.innerHTML = '';

    const iframe = document.createElement('iframe');
    iframe.title = 'Webstudio Upstream Builder';
    iframe.src = url.toString();
    iframe.style.cssText = 'position:fixed;inset:0;width:100vw;height:100vh;border:0;background:#fff;';
    iframe.setAttribute('allow', 'clipboard-read; clipboard-write; fullscreen');

    const upstreamOrigin = url.origin;

    const postToUpstream = (type, payload = {}) => {
        if (!iframe.contentWindow) {
            return;
        }

        iframe.contentWindow.postMessage({
            namespace,
            type,
            payload,
        }, upstreamOrigin || '*');
    };

    const relayParentMessage = (type, payload = {}) => {
        postToUpstream(type, {
            ...payload,
            contract: {
                namespace,
                protocolVersion: PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
                parentToChild: PAGE_BUILDER_IFRAME_PARENT_TO_CHILD,
                childToParent: PAGE_BUILDER_IFRAME_CHILD_TO_PARENT,
            },
            pagify: {
                accessToken,
                mode: String(boot.mode ?? 'page-builder'),
                theme: String(boot.theme ?? ''),
                pageId: String(boot.pageId ?? ''),
                siteId: String(boot.siteId ?? ''),
                parentOrigin: String(boot.parentOrigin ?? ''),
                endpoints: {
                    contractUrl: String(boot.contractUrl ?? ''),
                    tokenVerifyUrl: String(boot.tokenVerifyUrl ?? ''),
                    builderDataUrl,
                    mediaAssetsUrl,
                    mediaUploadUrl,
                },
            },
        });
    };

    const handleMessage = (event) => {
        const data = event?.data;

        if (!data || typeof data !== 'object') {
            return;
        }

        if (String(data.namespace ?? '') !== namespace) {
            return;
        }

        const type = String(data.type ?? '');
        const payload = data.payload ?? {};

        if (originGuard.isValidEvent(event) && event.source === window.parent) {
            if (type === PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.INIT
                || type === PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.SET_LAYOUT
                || type === PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.FLUSH
                || type === PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.SEARCH
                || type === PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.TOKEN_REFRESH_RESULT) {
                relayParentMessage(type, payload);
            }

            return;
        }

        if (event.origin !== upstreamOrigin) {
            return;
        }

        if (type === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.READY
            || type === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.ERROR
            || type === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.LAYOUT_CHANGE
            || type === PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.TOKEN_REFRESH_REQUEST
            || type === 'pagify:editor:telemetry-batch') {
            originGuard.postToParent(type, payload);
        }
    };

    iframe.addEventListener('load', () => {
        originGuard.postToParent(PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.READY, {
            protocolVersion: PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
            namespace,
            runtimeMode: 'upstream-embedded',
        });

        relayParentMessage(PAGE_BUILDER_IFRAME_PARENT_TO_CHILD.INIT, {
            protocolVersion: PAGE_BUILDER_IFRAME_PROTOCOL_VERSION,
            accessToken,
            tokenExpiresAt: boot.tokenExpiresAt ?? null,
            contractUrl: String(boot.contractUrl ?? ''),
            tokenRefreshUrl: String(boot.tokenRefreshUrl ?? ''),
            tokenVerifyUrl: String(boot.tokenVerifyUrl ?? ''),
            layout: {},
            context: {
                activeTheme: String(boot.theme ?? ''),
                breakpoints: [],
                blocks: [],
                canvasStyles: [],
            },
        });
    });

    iframe.addEventListener('error', () => {
        originGuard.postToParent(PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.ERROR, {
            message: 'Failed to load upstream embedded builder.',
        });
    });

    window.addEventListener('message', handleMessage);

    root.appendChild(iframe);

    return true;
};

if (runtimeMode === 'upstream-embedded') {
    setRootMessage('Verifying editor access token...');

    verifyEditorToken({
        token: accessToken,
        verifyUrl: String(boot.tokenVerifyUrl ?? ''),
    }).then((verificationResult) => {
        if (!verificationResult.ok) {
            setRootMessage(verificationResult.message, 'error');
            originGuard.postToParent(PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.ERROR, {
                message: verificationResult.message,
            });
            return;
        }

        telemetry.track('upstream-embedded.mounted');
        mountUpstreamEmbeddedRuntime();
    });
} else {
const webstudioAdapter = createWebstudioAdapter({ boot, telemetry });
telemetry.track('webstudio.runtime-mode', {
    runtimeMode: String(boot.runtimeMode ?? 'dom-preview'),
});

const render = () => {
    renderEditorHostReactApp(root, {
        state: {
            boot,
            layout,
            media: mediaState,
            webstudio: webstudioState,
        },
        actions: {
            onLayoutInput(value) {
                layout = parseTextareaLayout(value, layout);
                bridge?.layoutChange(false);
                render();
            },
            onMarkupChange(html, css, flush = false) {
                layout = applyWebstudioMarkup(layout, { html, css });
                bridge?.layoutChange(Boolean(flush));
                render();
            },
            onInsertPreset(markup, flush = false) {
                layout = appendWebstudioBlock(layout, markup);
                bridge?.layoutChange(Boolean(flush));
                render();
            },
            onFlush() {
                bridge?.layoutChange(true);
            },
            onMediaSearch(query) {
                loadMedia(query);
            },
            onMediaFileSelected(file) {
                uploadMedia(file);
            },
            onMediaSelect(asset) {
                layout = attachAssetToLayout(layout, asset);
                bridge?.layoutChange(false);
                render();
            },
            mountWebstudio(node) {
                webstudioMountNode = node;
                webstudioAdapter.mount(node, { layout });
                webstudioState = webstudioAdapter.getState();
            },
            updateWebstudio() {
                webstudioAdapter.update({ layout });
                webstudioState = webstudioAdapter.getState();
            },
            unmountWebstudio() {
                webstudioAdapter.unmount();
                webstudioMountNode = null;
                webstudioState = webstudioAdapter.getState();
            },
        },
    });
};

const setMediaState = (nextState) => {
    mediaState = {
        ...mediaState,
        ...nextState,
    };

    render();
};

const loadMedia = async (query = '') => {
    setMediaState({
        status: 'Loading media...',
        query: String(query ?? ''),
    });

    const result = await loadMediaAssets({
        token: accessToken,
        endpoint: mediaAssetsUrl,
        query,
    });

    if (!result.ok) {
        telemetry.track('media.load.failed', { message: result.message });
        setMediaState({
            status: result.message,
            assets: [],
        });

        return;
    }

    telemetry.track('media.load.success', {
        count: result.assets.length,
    });

    setMediaState({
        status: `Loaded ${result.assets.length} media assets.`,
        assets: result.assets,
    });
};

const uploadMedia = async (file) => {
    if (!(file instanceof File)) {
        setMediaState({
            status: 'Please choose a file before upload.',
        });

        return;
    }

    setMediaState({
        status: `Uploading ${file.name}...`,
    });

    const result = await uploadMediaAsset({
        token: accessToken,
        endpoint: mediaUploadUrl,
        file,
    });

    if (!result.ok) {
        telemetry.track('media.upload.failed', { message: result.message });
        setMediaState({
            status: result.message,
        });

        return;
    }

    telemetry.track('media.upload.success');

    if (result.asset) {
        layout = attachAssetToLayout(layout, result.asset);
        bridge?.layoutChange(false);
    }

    setMediaState({
        status: 'Upload complete.',
    });

    await loadMedia(mediaState.query);

    if (webstudioMountNode !== null) {
        webstudioAdapter.update({ layout });
        webstudioState = webstudioAdapter.getState();
        render();
    }
};

const attachBridge = () => {
    bridge = createPostMessageBridge({
        originGuard,
        namespace,
        parentToChild: PAGE_BUILDER_IFRAME_PARENT_TO_CHILD,
        childToParent: PAGE_BUILDER_IFRAME_CHILD_TO_PARENT,
        telemetry,
        getLayout: () => layout,
        setLayout: (nextLayout) => {
            layout = normalizeLayout(nextLayout);
        },
        rerender: render,
    });

    bridge.attach();
};

const bootstrapBuilderDataAndMedia = async () => {
    const builderDataResult = await loadBuilderData({
        token: accessToken,
        endpoint: builderDataUrl,
    });

    if (builderDataResult.ok) {
        layout = normalizeLayout(builderDataResult.layout);
        telemetry.track('builder-data.loaded');
        if (webstudioMountNode !== null) {
            webstudioAdapter.update({ layout });
            webstudioState = webstudioAdapter.getState();
        }
        render();
    } else {
        telemetry.track('builder-data.unavailable', {
            message: builderDataResult.message,
        });
    }

    await loadMedia('');
};

setRootMessage('Verifying editor access token...');

verifyEditorToken({
    token: accessToken,
    verifyUrl: String(boot.tokenVerifyUrl ?? ''),
}).then(async (verificationResult) => {
    if (!verificationResult.ok) {
        setRootMessage(verificationResult.message, 'error');
        bridge?.error(verificationResult.message);
        if (!bridge) {
            originGuard.postToParent(PAGE_BUILDER_IFRAME_CHILD_TO_PARENT.ERROR, {
                message: verificationResult.message,
            });
        }
        return;
    }

    attachBridge();
    render();
    bridge?.ready(PAGE_BUILDER_IFRAME_PROTOCOL_VERSION);

    // Load heavy runtime data after handshake to avoid parent timeout.
    await bootstrapBuilderDataAndMedia();
});
}
