const defaultWebstudioLayout = () => ({
    type: 'webstudio',
    webstudio: {
        html: '<main data-pbx-content-slot="true"><section class="pbx-section"><h2 class="pbx-subheading">Start building content</h2><p class="pbx-text">Use the right-side tools to compose your page.</p></section></main>',
        css: '.pbx-section{padding:16px;border:1px dashed #cbd5e1;border-radius:10px;background:#f8fafc}.pbx-subheading{margin:0 0 8px;color:#0f172a}.pbx-text{margin:0;color:#475569}',
    },
});

export const normalizeLayout = (layout) => {
    if (!layout || typeof layout !== 'object' || Array.isArray(layout)) {
        return defaultWebstudioLayout();
    }

    const normalized = {
        ...layout,
    };

    if (String(normalized.type ?? '').trim() === '') {
        normalized.type = 'webstudio';
    }

    if (normalized.type !== 'webstudio') {
        return normalized;
    }

    const webstudio = normalized.webstudio && typeof normalized.webstudio === 'object' && !Array.isArray(normalized.webstudio)
        ? { ...normalized.webstudio }
        : {};

    const html = typeof webstudio.html === 'string' ? webstudio.html.trim() : '';

    if (html === '') {
        const starter = defaultWebstudioLayout();
        webstudio.html = starter.webstudio.html;
        if (typeof webstudio.css !== 'string' || webstudio.css.trim() === '') {
            webstudio.css = starter.webstudio.css;
        }
    }

    normalized.webstudio = webstudio;

    return normalized;
};

export const parseTextareaLayout = (value, fallbackLayout) => {
    try {
        const parsed = JSON.parse(String(value ?? '{}'));
        return normalizeLayout(parsed);
    } catch (_) {
        return fallbackLayout;
    }
};

export const applyWebstudioMarkup = (layout, { html, css }) => {
    const normalized = normalizeLayout(layout);

    if (normalized.type !== 'webstudio') {
        return normalized;
    }

    return {
        ...normalized,
        webstudio: {
            ...(normalized.webstudio ?? {}),
            html: String(html ?? ''),
            css: String(css ?? ''),
        },
    };
};

export const appendWebstudioBlock = (layout, blockMarkup) => {
    const normalized = normalizeLayout(layout);

    if (normalized.type !== 'webstudio') {
        return normalized;
    }

    const currentHtml = String(normalized.webstudio?.html ?? '');
    const block = String(blockMarkup ?? '').trim();

    if (block === '') {
        return normalized;
    }

    return {
        ...normalized,
        webstudio: {
            ...(normalized.webstudio ?? {}),
            html: `${currentHtml}${currentHtml.trim() === '' ? '' : '\n'}${block}`,
        },
    };
};
