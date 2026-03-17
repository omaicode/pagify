export const createWebstudioAdapter = ({ boot, telemetry }) => {
    let mountNode = null;
    let canvasIframe = null;
    let mounted = false;

    const normalizeRuntimeMode = (value) => {
        const mode = String(value ?? '').trim();

        if (mode === 'upstream-experimental' || mode === 'dom-preview') {
            return mode;
        }

        return 'dom-preview';
    };

    const runtimeMode = normalizeRuntimeMode(boot.runtimeMode ?? 'dom-preview');
    const capabilities = {
        domPreview: true,
        upstreamMount: runtimeMode === 'upstream-experimental',
        mediaBridge: true,
        postMessageBridge: true,
    };

    const state = {
        runtimeMode,
        capabilities,
        mounted: false,
        status: 'idle',
        message: 'Adapter idle.',
        updatedAt: null,
        warnings: [],
    };

    const updateState = (next) => {
        Object.assign(state, next, {
            updatedAt: new Date().toISOString(),
        });
    };

    const extractMarkup = (layout) => {
        const normalizedLayout = layout && typeof layout === 'object' && !Array.isArray(layout) ? layout : {};
        const webstudio = normalizedLayout.webstudio && typeof normalizedLayout.webstudio === 'object' && !Array.isArray(normalizedLayout.webstudio)
            ? normalizedLayout.webstudio
            : {};
        const documentNode = webstudio.document && typeof webstudio.document === 'object' && !Array.isArray(webstudio.document)
            ? webstudio.document
            : {};

        const html = typeof webstudio.html === 'string'
            ? webstudio.html
            : (typeof documentNode.html === 'string' ? documentNode.html : '');

        let css = typeof webstudio.css === 'string' ? webstudio.css : '';

        if (css.trim() === '' && Array.isArray(webstudio.styles)) {
            css = webstudio.styles.filter((item) => typeof item === 'string').join('\n');
        }

        return {
            html,
            css,
            hasContent: html.trim() !== '' || css.trim() !== '',
        };
    };

    const renderCanvas = (layout) => {
        if (!canvasIframe) {
            return;
        }

        const markup = extractMarkup(layout);
        const srcdoc = `<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">${markup.css.trim() !== '' ? `<style>${markup.css}</style>` : ''}</head><body>${markup.html || '<main style="font-family: ui-sans-serif, system-ui, sans-serif; color: #475569; padding: 14px;">No webstudio markup yet.</main>'}</body></html>`;

        canvasIframe.srcdoc = srcdoc;

        const warnings = [];

        if (runtimeMode === 'upstream-experimental') {
            warnings.push('upstream-experimental mode is enabled; runtime currently uses dom-preview fallback while upstream mount integration is being completed.');
        }

        updateState({
            status: markup.hasContent ? 'rendered' : 'empty',
            message: markup.hasContent ? 'Rendered layout preview in adapter canvas.' : 'Waiting for webstudio markup payload.',
            warnings,
        });
    };

    const mount = (node, context) => {
        mountNode = node;

        if (!mountNode) {
            return;
        }

        mountNode.innerHTML = '';

        const panel = document.createElement('section');
        panel.style.cssText = 'border: 1px dashed #334155; border-radius: 10px; padding: 12px; background: #f8fafc;';

        const title = document.createElement('h3');
        title.textContent = 'Webstudio Mount Zone (Phase 3)';
        title.style.cssText = 'margin: 0 0 6px; font-size: 14px; color: #0f172a;';

        const info = document.createElement('p');
        info.style.cssText = 'margin: 0; font-size: 12px; color: #475569;';
        info.textContent = `theme=${String(boot.theme ?? '')} mode=${String(boot.mode ?? 'page-builder')} runtime=${runtimeMode} layoutType=${String(context?.layout?.type ?? 'unknown')}`;

        canvasIframe = document.createElement('iframe');
        canvasIframe.title = 'Webstudio adapter preview canvas';
        canvasIframe.style.cssText = 'width: 100%; min-height: 240px; margin-top: 10px; border: 1px solid #cbd5e1; border-radius: 8px; background: #ffffff;';
        canvasIframe.setAttribute('sandbox', 'allow-same-origin allow-scripts');

        panel.appendChild(title);
        panel.appendChild(info);
        panel.appendChild(canvasIframe);
        mountNode.appendChild(panel);

        mounted = true;
        updateState({
            mounted: true,
            status: 'mounted',
            message: runtimeMode === 'upstream-experimental'
                ? 'Adapter mounted in upstream-experimental mode (dom-preview fallback active).'
                : 'Adapter mounted and waiting for layout payload.',
            warnings: runtimeMode === 'upstream-experimental'
                ? ['upstream-experimental mode is enabled; runtime currently uses dom-preview fallback while upstream mount integration is being completed.']
                : [],
        });

        renderCanvas(context?.layout ?? {});

        telemetry.track('webstudio.adapter.mounted');
    };

    const update = (context) => {
        if (!mountNode || !mounted) {
            return;
        }

        const info = mountNode.querySelector('p');

        if (info) {
            info.textContent = `theme=${String(boot.theme ?? '')} mode=${String(boot.mode ?? 'page-builder')} runtime=${runtimeMode} layoutType=${String(context?.layout?.type ?? 'unknown')}`;
        }

        renderCanvas(context?.layout ?? {});
        telemetry.track('webstudio.adapter.updated');
    };

    const unmount = () => {
        if (mountNode) {
            mountNode.innerHTML = '';
        }

        mountNode = null;
        canvasIframe = null;
        mounted = false;
        updateState({
            mounted: false,
            status: 'unmounted',
            message: 'Adapter unmounted.',
            warnings: [],
        });
        telemetry.track('webstudio.adapter.unmounted');
    };

    return {
        mount,
        update,
        unmount,
        getState() {
            return { ...state };
        },
    };
};
