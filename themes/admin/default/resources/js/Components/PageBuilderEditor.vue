<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
    simpleMode: {
        type: Boolean,
        default: true,
    },
    primaryBlockKeys: {
        type: Array,
        default: () => [],
    },
    canvasStyles: {
        type: Array,
        default: () => [],
    },
    activeTheme: {
        type: String,
        default: '',
    },
    layouts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue']);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const editorContainer = ref(null);
const isEditorLoading = ref(true);
const editorLoadError = ref('');

let editor = null;
let syncTimeout = null;
let unmounted = false;
let relocatingComponent = false;

const emitLayoutSnapshot = () => {
    if (!editor) {
        return;
    }

    const grapesHtml = extractContentFromHtml(editor.getHtml());

    emit('update:modelValue', {
        ...layout.value,
        type: 'grapesjs',
        theme_layout: selectedThemeLayout.value,
        grapes: {
            html: grapesHtml,
            css: editor.getCss(),
            projectData: editor.getProjectData(),
            updated_at: new Date().toISOString(),
        },
    });
};

const defaultBlockCss = `
.pbx-section{padding:clamp(8px,1.2vw,14px);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111827}
main[data-pbx-content-slot="true"], .pbx-layout-content{padding-left:0!important;padding-right:0!important;padding-top: 1rem; padding-bottom: 1rem;}
.pbx-layout-content{min-height:220px;display:flex;flex-direction:column;gap:18px}
.pbx-layout-content > .pbx-section + .pbx-section{margin-top:18px}
.pbx-heading{font-size:clamp(1.6rem,3vw,2.4rem);line-height:1.2;font-weight:700;margin:0 0 12px;color:#0f172a}
.pbx-subheading{font-size:clamp(1.05rem,1.8vw,1.3rem);line-height:1.35;font-weight:600;margin:0 0 8px;color:#0f172a}
.pbx-text{font-size:1rem;line-height:1.7;color:#475569;margin:0}
.pbx-caption{font-size:.875rem;line-height:1.5;color:#64748b;margin-top:8px}
.pbx-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#4b3fd8,#7c3aed);color:#fff;text-decoration:none;font-weight:600;font-size:.95rem;border-radius:999px;padding:10px 18px;box-shadow:0 10px 22px rgba(79,70,229,.25);transition:transform .2s ease,box-shadow .2s ease}
.pbx-btn:hover{transform:translateY(-1px);box-shadow:0 14px 28px rgba(79,70,229,.3)}
.pbx-btn--light{background:#fff;color:#4b3fd8;box-shadow:none}
.pbx-image{width:100%;height:auto;border-radius:16px;display:block;object-fit:cover;box-shadow:0 16px 40px rgba(15,23,42,.14)}
.pbx-columns{display:grid;gap:16px}
.pbx-columns-2{grid-template-columns:repeat(2,minmax(0,1fr))}
.pbx-columns-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.pbx-grid{display:grid;gap:16px;grid-template-columns:repeat(4,minmax(0,1fr))}
.pbx-card{padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;box-shadow:0 8px 24px rgba(15,23,42,.06)}
.pbx-video{position:relative;border-radius:16px;overflow:hidden;background:#000;padding-top:56.25%;box-shadow:0 14px 34px rgba(15,23,42,.2)}
.pbx-video iframe{position:absolute;inset:0;width:100%;height:100%;border:0}
.pbx-link-card{display:grid;gap:8px;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff;text-decoration:none;color:inherit;transition:border-color .2s ease,transform .2s ease,box-shadow .2s ease}
.pbx-link-card:hover{border-color:#c4b5fd;transform:translateY(-1px);box-shadow:0 12px 24px rgba(91,33,182,.12)}
.pbx-link-card__eyebrow{font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;color:#7c3aed;font-weight:700}
.pbx-link-card__title{font-size:1.1rem;font-weight:700;color:#111827}
.pbx-link-card__desc{font-size:.95rem;line-height:1.65;color:#64748b}
.pbx-hero{display:grid;gap:16px;padding:clamp(28px,5vw,56px);border-radius:24px;background:radial-gradient(circle at top right,#dbeafe 0,#eef2ff 35%,#f8fafc 100%);border:1px solid #e2e8f0}
.pbx-eyebrow{margin:0 0 8px;font-size:.74rem;letter-spacing:.08em;text-transform:uppercase;color:#7c3aed;font-weight:700}
.pbx-hero__title{margin:0 0 10px;font-size:clamp(1.8rem,4vw,3rem);line-height:1.15;color:#0f172a}
.pbx-hero__text{margin:0 0 18px;font-size:1.05rem;line-height:1.75;color:#475569}
.pbx-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.pbx-stat{padding:18px;border-radius:14px;background:#0f172a;color:#f8fafc;display:grid;gap:4px}
.pbx-stat strong{font-size:1.5rem;line-height:1.2}
.pbx-stat span{font-size:.9rem;color:#cbd5e1}
.pbx-quote{margin:0;padding:22px;border-radius:16px;border:1px solid #e2e8f0;background:#fff;display:grid;gap:14px;color:#334155;font-size:1.05rem;line-height:1.8;box-shadow:0 10px 28px rgba(15,23,42,.08)}
.pbx-quote cite{font-style:normal;display:grid;gap:2px;color:#475569}
.pbx-quote cite span{font-weight:700;color:#0f172a}
.pbx-quote cite small{font-size:.85rem;color:#64748b}
.pbx-cta{padding:clamp(26px,4vw,44px);border-radius:18px;background:linear-gradient(135deg,#4b3fd8,#7c3aed);display:grid;gap:12px;color:#f8faff}
.pbx-cta__title{margin:0;font-size:clamp(1.4rem,3vw,2rem);line-height:1.2;color:#fff}
.pbx-cta__text{margin:0;color:#e9ddff;font-size:1rem;line-height:1.7}
.pbx-header{position:relative;background:#ffffff;border-bottom:1px solid #e5e7eb}
.pbx-header__inner{max-width:1120px;margin:0 auto;padding:14px clamp(16px,3vw,26px);display:flex;align-items:center;justify-content:space-between;gap:14px}
.pbx-header__brand{font-weight:700;color:#0f172a;text-decoration:none;font-size:1.05rem}
.pbx-header__nav{display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.pbx-header__nav a{color:#475569;text-decoration:none;font-size:.95rem;font-weight:500}
.pbx-footer{background:#0f172a;color:#e2e8f0;padding:clamp(22px,4vw,40px) clamp(16px,3vw,24px)}
.pbx-footer__grid{max-width:1120px;margin:0 auto;display:grid;gap:16px;grid-template-columns:2fr 1fr 1fr 1fr}
.pbx-footer h3,.pbx-footer h4{margin:0 0 10px;color:#fff}
.pbx-footer p{margin:0;color:#cbd5e1;line-height:1.6}
.pbx-footer a{display:block;color:#cbd5e1;text-decoration:none;margin-bottom:8px;font-size:.92rem}
.pbx-footer__meta{max-width:1120px;margin:16px auto 0;padding-top:14px;border-top:1px solid rgba(148,163,184,.25);font-size:.85rem;color:#94a3b8}
.pbx-pricing{display:grid;gap:16px;grid-template-columns:repeat(3,minmax(0,1fr))}
.pbx-pricing-card{position:relative;padding:20px;border:1px solid #e5e7eb;border-radius:16px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.08);display:grid;gap:12px}
.pbx-pricing-card h3{margin:0;color:#0f172a}
.pbx-pricing-card__price{margin:0;font-size:2rem;font-weight:700;color:#111827}
.pbx-pricing-card__price span{font-size:.95rem;font-weight:500;color:#64748b;margin-left:4px}
.pbx-pricing-card ul{margin:0;padding-left:18px;color:#475569;display:grid;gap:6px}
.pbx-pricing-card--featured{border-color:#a78bfa;box-shadow:0 16px 34px rgba(79,70,229,.2);transform:translateY(-3px)}
.pbx-pricing-card__badge{position:absolute;top:12px;right:12px;font-size:.72rem;font-weight:700;padding:4px 8px;border-radius:999px;background:#ede9fe;color:#5b21b6}
.pbx-faq{display:grid;gap:10px}
.pbx-faq details{border:1px solid #e2e8f0;border-radius:12px;background:#fff;padding:12px 14px}
.pbx-faq summary{cursor:pointer;font-weight:600;color:#0f172a}
.pbx-faq details p{margin:10px 0 0;color:#475569;line-height:1.7}
.pbx-contact{display:grid;gap:16px;grid-template-columns:1fr 1.2fr;align-items:start}
.pbx-contact__form{display:grid;gap:12px;padding:18px;border:1px solid #e5e7eb;border-radius:14px;background:#fff}
.pbx-contact__form label{display:grid;gap:6px;font-size:.9rem;color:#334155;font-weight:500}
.pbx-contact__form input,.pbx-contact__form textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px 12px;font-size:.95rem;line-height:1.5;outline:none}
.pbx-contact__form input:focus,.pbx-contact__form textarea:focus{border-color:#8b5cf6;box-shadow:0 0 0 3px rgba(139,92,246,.15)}
.pbx-header.gjs-selected,.pbx-header.gjs-hovered,.pbx-footer.gjs-selected,.pbx-footer.gjs-hovered,header.gjs-selected,header.gjs-hovered,footer.gjs-selected,footer.gjs-hovered{outline:none!important;box-shadow:none!important}
@media (max-width:1024px){.pbx-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:820px){.pbx-columns-2,.pbx-columns-3,.pbx-stats,.pbx-pricing,.pbx-contact,.pbx-footer__grid{grid-template-columns:1fr}.pbx-section{padding:16px}.pbx-grid{grid-template-columns:1fr}.pbx-header__inner{flex-wrap:wrap}.pbx-header__nav{width:100%;justify-content:flex-start}}
`;

const layout = computed(() => props.modelValue ?? {});
const selectedThemeLayout = computed({
    get() {
        const current = layout.value?.theme_layout;

        if (typeof current === 'string' && current.trim() !== '') {
            return current;
        }

        const fallback = props.layouts[0]?.path;
        return typeof fallback === 'string' ? fallback : '';
    },
    set(value) {
        emit('update:modelValue', {
            ...layout.value,
            theme_layout: value,
        });
    },
});
const primaryBlockKeySet = computed(() => new Set(props.primaryBlockKeys.map((item) => String(item))));

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

        emitLayoutSnapshot();
    }, 250);
};

const extractContentFromHtml = (html) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<body>${html}</body>`, 'text/html');
    const slot = doc.body.querySelector('[data-pbx-content-slot="true"]');

    if (slot) {
        const slotHtml = slot.innerHTML.trim();

        if (slotHtml !== '') {
            return slotHtml;
        }

        // If users accidentally drop blocks outside the slot, salvage block-like nodes.
        const fallbackNodes = Array.from(doc.body.querySelectorAll('[class*="pbx-"]'))
            .filter((node) => !slot.contains(node));

        if (fallbackNodes.length > 0) {
            return fallbackNodes.map((node) => node.outerHTML).join('');
        }
    }

    return doc.body.innerHTML;
};

const handleFlushRequest = () => {
    if (syncTimeout !== null) {
        window.clearTimeout(syncTimeout);
    }

    emitLayoutSnapshot();
};

const mergeLayoutWithContent = (layoutHtml, contentHtml) => {
    const parser = new DOMParser();
    const doc = parser.parseFromString(`<body>${layoutHtml}</body>`, 'text/html');
    const slot = doc.body.querySelector('[data-pbx-content-slot="true"]');

    if (!slot) {
        return doc.body.innerHTML;
    }

    slot.innerHTML = contentHtml && contentHtml.trim() !== ''
        ? contentHtml
        : '<section class="pbx-section"><h2 class="pbx-subheading">Start building content</h2><p class="pbx-text">Drag and drop blocks here.</p></section>';

    return doc.body.innerHTML;
};

const mergeCssClasses = (existingClassValue, requestedClassValue) => {
    const existing = String(existingClassValue ?? '')
        .split(/\s+/)
        .map((item) => item.trim())
        .filter((item) => item !== '');
    const requested = String(requestedClassValue ?? '')
        .split(/\s+/)
        .map((item) => item.trim())
        .filter((item) => item !== '');

    const merged = [
        ...existing.filter((item) => item.startsWith('gjs-')),
        ...requested,
    ];

    return Array.from(new Set(merged)).join(' ').trim();
};

const applyCanvasBodyAttributes = (attributes) => {
    if (!editor) {
        return;
    }

    const body = editor.Canvas?.getBody?.();

    if (!body) {
        return;
    }

    const next = attributes && typeof attributes === 'object' ? attributes : {};
    const managed = new Set(['class']);

    Object.keys(next).forEach((key) => {
        if (typeof key === 'string' && key.toLowerCase().startsWith('data-')) {
            managed.add(key.toLowerCase());
        }
    });

    managed.forEach((attr) => {
        if (!(attr in next)) {
            body.removeAttribute(attr);
        }
    });

    Object.entries(next).forEach(([name, value]) => {
        if (typeof name !== 'string') {
            return;
        }

        const normalized = name.toLowerCase();

        if (normalized !== 'class' && !normalized.startsWith('data-')) {
            return;
        }

        if (normalized === 'class') {
            const merged = mergeCssClasses(body.getAttribute('class') ?? '', String(value ?? ''));

            if (merged !== '') {
                body.setAttribute('class', merged);
            } else {
                body.removeAttribute('class');
            }

            return;
        }

        body.setAttribute(normalized, String(value ?? ''));
    });
};

const applyCanvasHtmlAttributes = (attributes) => {
    if (!editor) {
        return;
    }

    const doc = editor.Canvas?.getDocument?.();
    const root = doc?.documentElement;

    if (!root) {
        return;
    }

    const next = attributes && typeof attributes === 'object' ? attributes : {};
    const managed = new Set(['lang', 'dir', 'class']);

    Object.keys(next).forEach((key) => {
        if (typeof key === 'string' && key.toLowerCase().startsWith('data-')) {
            managed.add(key.toLowerCase());
        }
    });

    managed.forEach((attr) => {
        if (!(attr in next)) {
            root.removeAttribute(attr);
        }
    });

    Object.entries(next).forEach(([name, value]) => {
        if (typeof name !== 'string') {
            return;
        }

        const normalized = name.toLowerCase();

        if (!['lang', 'dir', 'class'].includes(normalized) && !normalized.startsWith('data-')) {
            return;
        }

        if (normalized === 'class') {
            const merged = mergeCssClasses(root.getAttribute('class') ?? '', String(value ?? ''));

            if (merged !== '') {
                root.setAttribute('class', merged);
            } else {
                root.removeAttribute('class');
            }

            return;
        }

        root.setAttribute(normalized, String(value ?? ''));
    });
};

const applySelectedLayoutContext = (selected) => {
    applyCanvasHtmlAttributes(selected?.html_attributes ?? {});
    applyCanvasBodyAttributes(selected?.body_attributes ?? {});
    syncCanvasPreviewStyles(selected?.preview_styles ?? []);
    syncCanvasPreviewScripts(selected?.preview_scripts ?? []);
};

const hasContentSlotMarker = (attributes = {}) => {
    if (!attributes || typeof attributes !== 'object') {
        return false;
    }

    const rawMarker = attributes['data-pbx-content-slot'];

    if (typeof rawMarker !== 'undefined' && String(rawMarker).toLowerCase() !== 'false') {
        return true;
    }

    const classValue = String(attributes.class ?? '');
    return classValue.split(/\s+/).includes('pbx-layout-content');
};

const isInsideContentSlot = (component) => {
    let current = component;

    while (current) {
        const attributes = typeof current.getAttributes === 'function' ? current.getAttributes() : {};

        if (hasContentSlotMarker(attributes)) {
            return true;
        }

        current = typeof current.parent === 'function' ? current.parent() : null;
    }

    return false;
};

const findFirstContentSlotComponent = () => {
    if (!editor) {
        return null;
    }

    const wrapper = editor.getWrapper?.();

    if (!wrapper) {
        return null;
    }

    let found = null;

    const walk = (component) => {
        if (!component || found) {
            return;
        }

        const attributes = typeof component.getAttributes === 'function' ? component.getAttributes() : {};

        if (hasContentSlotMarker(attributes)) {
            found = component;
            return;
        }

        const children = typeof component.components === 'function' ? component.components() : null;

        if (children && typeof children.forEach === 'function') {
            children.forEach((child) => walk(child));
        }
    };

    walk(wrapper);
    return found;
};

const enforceDropInsideMain = (component) => {
    if (!component || relocatingComponent || isInsideContentSlot(component)) {
        return;
    }

    const slot = findFirstContentSlotComponent();

    if (!slot || slot === component) {
        if (typeof component.remove === 'function') {
            component.remove();
        }

        return;
    }

    const collection = typeof slot.components === 'function' ? slot.components() : null;

    if (!collection || typeof collection.add !== 'function') {
        if (typeof component.remove === 'function') {
            component.remove();
        }

        return;
    }

    relocatingComponent = true;
    collection.add(component);
    relocatingComponent = false;

    lockToContentOnly();
    scheduleSync();
};

const lockToContentOnly = () => {
    if (!editor) {
        return;
    }

    const wrapper = editor.getWrapper?.();

    if (!wrapper || typeof wrapper.find !== 'function') {
        return;
    }

    if (typeof wrapper.set === 'function') {
        wrapper.set('droppable', false);
        wrapper.set('draggable', false);
        wrapper.set('copyable', false);
        wrapper.set('removable', false);
        wrapper.set('editable', false);
        wrapper.set('selectable', false);
        wrapper.set('hoverable', false);
        wrapper.set('highlightable', false);
    }

    const walk = (component, insideContentSlot = false, isRoot = false) => {
        if (!component) {
            return;
        }

        const attributes = typeof component.getAttributes === 'function' ? component.getAttributes() : {};
        const isContentSlot = hasContentSlotMarker(attributes);
        const nextInsideContent = insideContentSlot || isContentSlot;

        if (!isRoot && typeof component.set === 'function') {
            if (nextInsideContent) {
                component.set('droppable', true);
                component.set('editable', true);
                component.set('selectable', true);
                component.set('hoverable', true);
                component.set('highlightable', true);

                if (isContentSlot) {
                    // Keep the content slot container itself fixed while allowing edits inside.
                    component.set('draggable', false);
                    component.set('copyable', false);
                    component.set('removable', false);
                } else {
                    component.set('draggable', true);
                    component.set('copyable', true);
                    component.set('removable', true);
                }
            } else {
                // Wrapper/header/footer/outside zones are fully locked.
                component.set('droppable', false);
                component.set('draggable', false);
                component.set('copyable', false);
                component.set('removable', false);
                component.set('editable', false);
                component.set('selectable', false);
                component.set('hoverable', false);
                component.set('highlightable', false);
            }
        }

        const children = typeof component.components === 'function' ? component.components() : null;

        if (children && typeof children.forEach === 'function') {
            children.forEach((child) => walk(child, nextInsideContent, false));
        }
    };

    walk(wrapper, false, true);
};

const applyCurrentLayoutContext = () => {
    if (!editor) {
        return;
    }

    const selected = props.layouts.find((item) => item?.path === selectedThemeLayout.value);

    if (!selected) {
        return;
    }

    applySelectedLayoutContext(selected);
    lockToContentOnly();
};

const syncCanvasPreviewScripts = (scripts) => {
    if (!editor) {
        return;
    }

    const doc = editor.Canvas?.getDocument?.();
    const target = doc?.body;

    if (!doc || !target) {
        return;
    }

    Array.from(doc.querySelectorAll('script[data-pbx-preview-script="true"]')).forEach((node) => {
        node.remove();
    });

    if (!Array.isArray(scripts)) {
        return;
    }

    scripts.forEach((item) => {
        const src = String(item?.src ?? '').trim();

        if (src === '') {
            return;
        }

        const script = doc.createElement('script');
        script.setAttribute('data-pbx-preview-script', 'true');
        script.src = src;

        if (typeof item?.type === 'string' && item.type.trim() !== '') {
            script.type = item.type.trim();
        }

        if (Boolean(item?.defer)) {
            script.defer = true;
        }

        if (Boolean(item?.async)) {
            script.async = true;
        }

        target.appendChild(script);
    });
};

const syncCanvasPreviewStyles = (styles) => {
    if (!editor) {
        return;
    }

    const doc = editor.Canvas?.getDocument?.();
    const head = doc?.head;

    if (!doc || !head) {
        return;
    }

    Array.from(doc.querySelectorAll('link[data-pbx-preview-style="true"]')).forEach((node) => {
        node.remove();
    });

    if (!Array.isArray(styles)) {
        return;
    }

    styles.forEach((item) => {
        const href = String(item?.href ?? '').trim();

        if (href === '') {
            return;
        }

        const link = doc.createElement('link');
        link.setAttribute('data-pbx-preview-style', 'true');
        link.rel = 'stylesheet';
        link.href = href;

        if (typeof item?.media === 'string' && item.media.trim() !== '') {
            link.media = item.media.trim();
        }

        head.appendChild(link);
    });
};

const applySelectedLayout = () => {
    if (!editor) {
        return;
    }

    const selected = props.layouts.find((item) => item?.path === selectedThemeLayout.value);

    if (!selected || typeof selected.editor_html !== 'string' || selected.editor_html.trim() === '') {
        return;
    }

    applySelectedLayoutContext(selected);

    const currentContent = extractContentFromHtml(editor.getHtml());
    const mergedHtml = mergeLayoutWithContent(selected.editor_html, currentContent);

    editor.setComponents(mergedHtml);

    window.requestAnimationFrame(() => {
        applySelectedLayoutContext(selected);
        lockToContentOnly();
    });

    scheduleSync();
};

const loadLayoutToEditor = () => {
    if (!editor) {
        return;
    }

    const grapesPayload = (layout.value?.grapes ?? {});
    const projectData = grapesPayload?.projectData;

    if (projectData && typeof projectData === 'object') {
        editor.loadProjectData(projectData);

        window.requestAnimationFrame(() => {
            lockToContentOnly();
        });

        return;
    }

    const html = typeof grapesPayload?.html === 'string' ? grapesPayload.html : '';
    const css = typeof grapesPayload?.css === 'string' ? grapesPayload.css : '';

    if (html !== '') {
        editor.setComponents(html);
        lockToContentOnly();
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
    const fallbackText = block?.label ?? block?.key ?? label('pb_block', 'Block');
    const htmlTemplate = (block?.html_template ?? '').toString().trim();

    if (htmlTemplate !== '') {
        return htmlTemplate;
    }

    return `<section class="pbx-section"><h3 class="pbx-subheading">${fallbackText}</h3><p class="pbx-text">${label('pb_edit_block_in_editor', 'Edit this block in GrapesJS.')}</p></section>`;
};

const iconByKey = {
    heading: 'type',
    paragraph: 'align-left',
    button: 'cursor-click',
    image: 'image',
    'columns-2': 'columns-2',
    'columns-3': 'columns-3',
    'video-embed': 'video',
    'link-card': 'link',
    'card-grid': 'grid',
    'hero-banner': 'sparkles',
    'stats-row': 'chart',
    testimonial: 'quote',
    'cta-panel': 'rocket',
    'site-header': 'header',
    'site-footer': 'footer',
    'pricing-table': 'pricing',
    'faq-list': 'faq',
    'contact-form': 'mail',
};

const svgPaths = {
    type: '<path d="M4 6h16M9 6v12M6 18h6"/><path d="M12 10h8"/>',
    'align-left': '<path d="M4 7h16M4 12h12M4 17h16"/>',
    'cursor-click': '<path d="M7 4v12l3-3 2 5 3-1-2-5 4-1L7 4Z"/>',
    image: '<rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="m7 15 3-3 2 2 3-4 3 5"/><circle cx="8" cy="9" r="1"/>',
    'columns-2': '<rect x="3.5" y="5" width="7.5" height="14" rx="1.5"/><rect x="12.5" y="5" width="8" height="14" rx="1.5"/>',
    'columns-3': '<rect x="3.5" y="5" width="5" height="14" rx="1.5"/><rect x="9.5" y="5" width="5" height="14" rx="1.5"/><rect x="15.5" y="5" width="5" height="14" rx="1.5"/>',
    video: '<rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="m10 9 5 3-5 3Z"/>',
    link: '<path d="M9 8h-2a4 4 0 1 0 0 8h2"/><path d="M15 8h2a4 4 0 1 1 0 8h-2"/><path d="M8 12h8"/>',
    grid: '<rect x="4" y="5" width="6" height="6" rx="1"/><rect x="14" y="5" width="6" height="6" rx="1"/><rect x="4" y="13" width="6" height="6" rx="1"/><rect x="14" y="13" width="6" height="6" rx="1"/>',
    sparkles: '<path d="m12 4 1.4 3.4L17 8.8l-3.6 1.4L12 14l-1.4-3.8L7 8.8l3.6-1.4L12 4Z"/><path d="m19 14 .8 1.9L22 16.7l-2.2.8L19 19.5l-.8-2-2.2-.8 2.2-.8L19 14Z"/>',
    chart: '<path d="M5 18V10"/><path d="M10 18V6"/><path d="M15 18v-4"/><path d="M20 18V8"/><path d="M4 18h17"/>',
    quote: '<path d="M8 11H5v-1a3 3 0 0 1 3-3M19 11h-3v-1a3 3 0 0 1 3-3"/><rect x="4" y="11" width="6" height="6" rx="1.2"/><rect x="14" y="11" width="6" height="6" rx="1.2"/>',
    rocket: '<path d="M13 5c2.8-.7 5.7.2 7 1.4-1.2 1.3-2 4.2-1.4 7-2.8.7-5.7-.2-7-1.4-1.2-1.3-2-4.2-1.4-7 .3-.1.5-.1.8-.2Z"/><path d="m9 15-3 3"/><path d="m8 17-2 2"/><circle cx="16.5" cy="8.5" r="1.2"/>',
    header: '<rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="M3.5 9h17"/><path d="M7 7h2"/><path d="M13 7h5"/>',
    footer: '<rect x="3.5" y="5" width="17" height="14" rx="2"/><path d="M3.5 15h17"/><path d="M7 17h4"/><path d="M14 17h3"/>',
    pricing: '<path d="M12 4v16"/><path d="M16 8.5c0-1.6-1.8-2.5-4-2.5s-4 .9-4 2.5 1.8 2.5 4 2.5 4 .9 4 2.5-1.8 2.5-4 2.5-4-.9-4-2.5"/>',
    faq: '<circle cx="12" cy="12" r="8.5"/><path d="M9.8 9.8a2.6 2.6 0 1 1 4.5 1.8c-.8.8-1.6 1.2-1.6 2.4"/><circle cx="12" cy="16.8" r=".8"/>',
    mail: '<rect x="3.5" y="6" width="17" height="12" rx="2"/><path d="m4.5 7 7.5 6 7.5-6"/>',
    generic: '<rect x="4" y="5" width="16" height="14" rx="2"/><path d="M8 9h8M8 13h8M8 17h5"/>',
};

const iconTones = {
    Typography: ['#5b21b6', '#8b5cf6'],
    Actions: ['#4338ca', '#6366f1'],
    Media: ['#7c3aed', '#a855f7'],
    Layout: ['#4f46e5', '#7c3aed'],
    Sections: ['#6d28d9', '#8b5cf6'],
    Data: ['#0f766e', '#14b8a6'],
    'Social Proof': ['#be185d', '#ec4899'],
    Commerce: ['#b45309', '#f59e0b'],
    Support: ['#0369a1', '#38bdf8'],
    Forms: ['#065f46', '#10b981'],
    'Plugin Blocks': ['#1d4ed8', '#60a5fa'],
    General: ['#5b21b6', '#8b5cf6'],
};

const resolveBlockIconSvg = (block) => {
    const iconKey = iconByKey[block?.key] ?? 'generic';
    const paths = svgPaths[iconKey] ?? svgPaths.generic;
    const [primary, accent] = iconTones[block?.category] ?? iconTones.General;

    return `<div style="display:flex;justify-content:center;align-items:center;padding:6px 0;"><div style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:10px;background:linear-gradient(145deg,${primary},${accent});box-shadow:0 8px 16px color-mix(in srgb, ${primary} 35%, transparent);"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="#fff" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">${paths}</svg><span style="position:absolute;right:-3px;bottom:-3px;width:9px;height:9px;border-radius:999px;background:${accent};border:2px solid #fff;"></span></div>`;
};

const ensureThemeLayoutDefault = () => {
    const current = layout.value?.theme_layout;

    if (typeof current === 'string' && current.trim() !== '') {
        return;
    }

    const fallback = props.layouts[0]?.path;

    if (typeof fallback !== 'string' || fallback.trim() === '') {
        return;
    }

    emit('update:modelValue', {
        ...layout.value,
        theme_layout: fallback,
    });
};

onMounted(async () => {
    if (editorContainer.value === null) {
        isEditorLoading.value = false;
        return;
    }

    isEditorLoading.value = true;
    editorLoadError.value = '';
    ensureThemeLayoutDefault();

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
            canvas: {
                styles: [
                    'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
                    ...props.canvasStyles,
                ],
            },
            selectorManager: { componentFirst: true },
            deviceManager: {
                devices: props.breakpoints.map((breakpoint) => ({
                    id: breakpoint,
                    name: breakpoint,
                    width: resolveDeviceWidth(breakpoint),
                })),
            },
        });

        const themeCategoryId = 'pb-theme-blocks';
        const themeCategoryLabel = label('pb_theme_blocks', 'Theme Blocks');
        editor.BlockManager.getCategories().add({
            id: themeCategoryId,
            label: themeCategoryLabel,
            open: true,
        });
        const sortedBlocks = [...props.blocks].sort((left, right) => {
            const leftTheme = primaryBlockKeySet.value.has(String(left?.key ?? '')) ? 0 : 1;
            const rightTheme = primaryBlockKeySet.value.has(String(right?.key ?? '')) ? 0 : 1;

            if (leftTheme !== rightTheme) {
                return leftTheme - rightTheme;
            }

            return String(left?.label ?? left?.key ?? '').localeCompare(String(right?.label ?? right?.key ?? ''));
        });

        sortedBlocks.forEach((block) => {
            const isThemeBlock = primaryBlockKeySet.value.has(String(block?.key ?? ''));

            editor.BlockManager.add(`pagify-${block.key}`, {
                label: block.label,
                category: isThemeBlock
                    ? {
                        id: themeCategoryId,
                        label: themeCategoryLabel,
                        open: true,
                    }
                    : (block.category || (block.source === 'plugin' ? label('pb_plugin_blocks', 'Plugin Blocks') : label('pb_core_blocks', 'Core Blocks'))),
                media: resolveBlockIconSvg(block),
                content: mapBlockContent(block),
                attributes: {
                    title: block.description || block.label,
                },
            });
        });

        editor.BlockManager.getCategories().each((category) => {
            category.set('open', category.get('id') === themeCategoryId);
        });

        editor.addStyle(defaultBlockCss);

        loadLayoutToEditor();
        const grapesPayload = layout.value?.grapes ?? {};
        const hasSavedProject = !!(grapesPayload?.projectData && typeof grapesPayload.projectData === 'object');
        const hasSavedHtml = typeof grapesPayload?.html === 'string' && grapesPayload.html.trim() !== '';

        if (!hasSavedProject && !hasSavedHtml) {
            applySelectedLayout();
        } else {
            applyCurrentLayoutContext();
        }

        editor.on('update', scheduleSync);
        editor.on('load', applyCurrentLayoutContext);
        editor.on('canvas:frame:load', applyCurrentLayoutContext);
        editor.on('component:mount', lockToContentOnly);
        editor.on('block:drag:stop', enforceDropInsideMain);
        window.addEventListener('pbx-editor-flush', handleFlushRequest);
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

    window.removeEventListener('pbx-editor-flush', handleFlushRequest);
});

watch(selectedThemeLayout, (next, previous) => {
    if (!editor || next === '' || next === previous) {
        return;
    }

    applySelectedLayout();
});
</script>

<template>
    <div class="space-y-3">
        <div class="rounded border border-slate-200 bg-white p-3">
            <p class="text-xs text-slate-500">
                {{ props.simpleMode
                    ? label('pb_editor_drag_drop_hint', 'Drag blocks from the left panel and drop directly into the canvas. Primary blocks are prioritized for quick use.')
                    : label('pb_editor_hint', 'GrapesJS canvas is active. Use the left block manager and top device switcher for responsive editing.') }}
            </p>
            <p v-if="activeTheme" class="mt-1 text-[11px] text-indigo-600">
                {{ label('pb_using_active_theme', 'Canvas is loaded with active theme styles:') }} {{ activeTheme }}
            </p>
        </div>

        <div v-if="isEditorLoading" class="rounded border border-slate-200 bg-white p-3 text-sm text-slate-600">
            {{ label('pb_loading_editor', 'Loading GrapesJS editor...') }}
        </div>

        <div v-if="editorLoadError !== ''" class="rounded border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            {{ editorLoadError }}
        </div>

        <div v-show="!isEditorLoading && editorLoadError === ''" ref="editorContainer" class="gjs-wrapper overflow-hidden rounded border border-slate-200" />
    </div>
</template>
