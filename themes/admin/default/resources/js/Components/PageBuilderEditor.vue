<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Fuse from 'fuse.js';
import { toast } from 'vue3-toastify';

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
    blockSearchConfig: {
        type: Object,
        default: () => ({}),
    },
    compactHeader: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['update:modelValue', 'editor-state-change']);
const page = usePage();
const t = computed(() => page.props.translations?.ui ?? {});
const label = (key, fallback) => t.value?.[key] ?? fallback;

const editorContainer = ref(null);
const isEditorLoading = ref(true);
const editorLoadError = ref('');
const editorSyncState = ref('synced');
const lastSyncedAt = ref('');
const blockSearchTerm = ref('');
const filteredBlockCount = ref(0);
const recentBlockKeys = ref([]);
const isFocusMode = ref(false);
const showEditorGuide = ref(true);
const editorHealth = ref({
    state: 'unknown',
    slotFound: false,
    contentBlocks: 0,
    outsideBlocks: 0,
    updatedAt: '',
});

let editor = null;
let syncTimeout = null;
let unmounted = false;
let relocatingComponent = false;
let registeredBlocks = [];
let activeDraggedBlockKey = null;
let blockSearchIndex = null;

const RECENT_BLOCK_STORAGE_KEY = 'pagify:page-builder:recent-blocks';
const RECENT_BLOCK_LIMIT = 6;
const EDITOR_PREFS_STORAGE_KEY = 'pagify:page-builder:editor-prefs';
const DEFAULT_BLOCK_SEARCH_OPTIONS = {
    includeScore: false,
    threshold: 0.34,
    ignoreLocation: true,
    minMatchCharLength: 2,
    keys: [
        { name: 'label', weight: 0.5 },
        { name: 'key', weight: 0.25 },
        { name: 'description', weight: 0.2 },
        { name: 'category', weight: 0.05 },
    ],
};

const editorCanvasHeight = computed(() => (isFocusMode.value ? '82vh' : '70vh'));

const editorHealthClass = computed(() => {
    if (editorHealth.value.state === 'warning') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (editorHealth.value.state === 'healthy') {
        return 'border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    return 'border-slate-200 bg-slate-50 text-slate-600';
});

const editorHealthLabel = computed(() => {
    if (editorHealth.value.state === 'warning') {
        return label('pb_health_status_warning', 'Layout needs attention');
    }

    if (editorHealth.value.state === 'healthy') {
        return label('pb_health_status_healthy', 'Layout healthy');
    }

    return label('pb_health_status_checking', 'Checking layout health...');
});

const editorSyncLabel = computed(() => {
    if (editorSyncState.value === 'syncing') {
        return label('pb_editor_state_syncing', 'Syncing changes...');
    }

    if (editorSyncState.value === 'dirty') {
        return label('pb_editor_state_unsaved', 'Unsaved changes');
    }

    return label('pb_editor_state_synced', 'Changes synced');
});

const editorSyncClass = computed(() => {
    if (editorSyncState.value === 'syncing') {
        return 'border-amber-200 bg-amber-50 text-amber-700';
    }

    if (editorSyncState.value === 'dirty') {
        return 'border-sky-200 bg-sky-50 text-sky-700';
    }

    return 'border-emerald-200 bg-emerald-50 text-emerald-700';
});

const markEditorDirty = () => {
    editorSyncState.value = 'dirty';
    emit('editor-state-change', {
        state: 'dirty',
        syncedAt: lastSyncedAt.value,
    });
};

const markEditorSynced = () => {
    editorSyncState.value = 'synced';
    lastSyncedAt.value = new Date().toLocaleTimeString();
    emit('editor-state-change', {
        state: 'synced',
        syncedAt: lastSyncedAt.value,
    });
};

const applyEditorCanvasHeight = () => {
    if (!editorContainer.value) {
        return;
    }

    editorContainer.value.style.height = editorCanvasHeight.value;

    if (editor && typeof editor.refresh === 'function') {
        editor.refresh();
    }
};

const loadEditorPreferences = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        const raw = window.localStorage.getItem(EDITOR_PREFS_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : {};

        isFocusMode.value = Boolean(parsed?.focusMode);
        showEditorGuide.value = parsed?.guideDismissed ? false : true;
    } catch (_) {
        isFocusMode.value = false;
        showEditorGuide.value = true;
    }
};

const persistEditorPreferences = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(EDITOR_PREFS_STORAGE_KEY, JSON.stringify({
            focusMode: isFocusMode.value,
            guideDismissed: !showEditorGuide.value,
        }));
    } catch (_) {
        // No-op: localStorage might be unavailable in restricted browser contexts.
    }
};

const clearBlockSearch = () => {
    blockSearchTerm.value = '';
};

const clearRecentBlocks = () => {
    recentBlockKeys.value = [];
    persistRecentBlockKeys();
    renderBlockManager(registeredBlocks, blockSearchTerm.value);
    toast.info(label('pb_recent_blocks_cleared', 'Quick blocks cleared.'));
};

const dismissEditorGuide = () => {
    showEditorGuide.value = false;
    persistEditorPreferences();
};

const emitLayoutSnapshot = () => {
    if (!editor) {
        return;
    }

    editorSyncState.value = 'syncing';
    emit('editor-state-change', {
        state: 'syncing',
        syncedAt: lastSyncedAt.value,
    });

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

    markEditorSynced();
    assessEditorHealth();
};

const assessEditorHealth = () => {
    if (!editor) {
        return;
    }

    const parser = new DOMParser();
    const doc = parser.parseFromString(`<body>${editor.getHtml()}</body>`, 'text/html');
    const slot = doc.body.querySelector('[data-pbx-content-slot="true"]');

    const allBlockNodes = Array.from(doc.body.querySelectorAll('[class*="pbx-"]'));
    const outsideBlocks = slot
        ? allBlockNodes.filter((node) => !slot.contains(node)).length
        : allBlockNodes.length;
    const contentBlocks = slot
        ? slot.querySelectorAll('[class*="pbx-"]').length
        : 0;

    const state = !slot || outsideBlocks > 0 ? 'warning' : 'healthy';

    editorHealth.value = {
        state,
        slotFound: !!slot,
        contentBlocks,
        outsideBlocks,
        updatedAt: new Date().toLocaleTimeString(),
    };
};

const handleGlobalKeydown = (event) => {
    const isSaveShortcut = (event.metaKey || event.ctrlKey) && String(event.key ?? '').toLowerCase() === 's';

    if (!isSaveShortcut) {
        return;
    }

    event.preventDefault();
    handleFlushRequest();
    toast.success(label('pb_hotkey_saved', 'Changes synced locally.'));
};

const handleExternalSearchEvent = (event) => {
    const term = String(event?.detail?.term ?? '');
    blockSearchTerm.value = term;
};

const defaultBlockCss = `
.pbx-section{padding:clamp(8px,1.2vw,14px);font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#111827}
main[data-pbx-content-slot="true"], .pbx-layout-content{padding-left:0!important;padding-right:0!important;padding-top: 1rem; padding-bottom: 1rem;}
.pbx-layout-content{min-height:220px;display:flex;flex-direction:column;gap:18px;border:1px dashed #cbd5e1;border-radius:10px;background:linear-gradient(180deg,rgba(248,250,252,.96),rgba(248,250,252,.75));box-shadow:inset 0 0 0 1px rgba(255,255,255,.6)}
.pbx-layout-content:empty::before{content:'Drop blocks here';display:block;padding:12px 14px;font-size:12px;color:#64748b}
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

const normalizeSearchValue = (value) => String(value ?? '').trim().toLowerCase();

const sanitizeBlockSearchOptions = (config) => {
    const input = config && typeof config === 'object' ? config : {};
    const threshold = Number(input.threshold);
    const minMatchCharLength = Number(input.minMatchCharLength);

    const keys = Array.isArray(input.keys) && input.keys.length > 0
        ? input.keys
            .map((item) => {
                if (typeof item === 'string') {
                    return { name: item, weight: 1 };
                }

                if (!item || typeof item !== 'object') {
                    return null;
                }

                const name = String(item.name ?? '').trim();
                const weight = Number(item.weight);

                if (name === '') {
                    return null;
                }

                return {
                    name,
                    weight: Number.isFinite(weight) && weight > 0 ? weight : 1,
                };
            })
            .filter((item) => item !== null)
        : DEFAULT_BLOCK_SEARCH_OPTIONS.keys;

    return {
        ...DEFAULT_BLOCK_SEARCH_OPTIONS,
        ...input,
        threshold: Number.isFinite(threshold)
            ? Math.min(1, Math.max(0, threshold))
            : DEFAULT_BLOCK_SEARCH_OPTIONS.threshold,
        minMatchCharLength: Number.isFinite(minMatchCharLength)
            ? Math.max(1, Math.floor(minMatchCharLength))
            : DEFAULT_BLOCK_SEARCH_OPTIONS.minMatchCharLength,
        keys,
    };
};

const resolvedBlockSearchOptions = computed(() => sanitizeBlockSearchOptions(props.blockSearchConfig));

const buildBlockSearchIndex = (blocks) => {
    blockSearchIndex = new Fuse(Array.isArray(blocks) ? blocks : [], resolvedBlockSearchOptions.value);
};

const findMatchingBlocks = (searchTerm, fallbackBlocks) => {
    const normalizedSearch = normalizeSearchValue(searchTerm);

    if (normalizedSearch === '') {
        return Array.isArray(fallbackBlocks) ? fallbackBlocks : [];
    }

    if (!blockSearchIndex) {
        buildBlockSearchIndex(fallbackBlocks);
    }

    return blockSearchIndex
        .search(normalizedSearch)
        .map((result) => result.item)
        .filter((item) => !!item);
};

const sanitizeRecentBlockKeys = (keys, availableBlockKeys) => {
    if (!Array.isArray(keys)) {
        return [];
    }

    const available = new Set(Array.isArray(availableBlockKeys) ? availableBlockKeys : []);

    return Array.from(new Set(
        keys
            .map((key) => String(key ?? '').trim())
            .filter((key) => key !== '' && available.has(key)),
    )).slice(0, RECENT_BLOCK_LIMIT);
};

const loadRecentBlockKeys = (availableBlockKeys) => {
    if (typeof window === 'undefined') {
        recentBlockKeys.value = [];
        return;
    }

    try {
        const raw = window.localStorage.getItem(RECENT_BLOCK_STORAGE_KEY);
        const parsed = raw ? JSON.parse(raw) : [];
        recentBlockKeys.value = sanitizeRecentBlockKeys(parsed, availableBlockKeys);
    } catch (_) {
        recentBlockKeys.value = [];
    }
};

const persistRecentBlockKeys = () => {
    if (typeof window === 'undefined') {
        return;
    }

    try {
        window.localStorage.setItem(RECENT_BLOCK_STORAGE_KEY, JSON.stringify(recentBlockKeys.value));
    } catch (_) {
        // No-op: localStorage might be unavailable in restricted browser contexts.
    }
};

const resolveBlockKeyFromBlockId = (blockId) => {
    const normalized = String(blockId ?? '').trim();

    if (!normalized.startsWith('pagify-')) {
        return null;
    }

    return normalized.slice('pagify-'.length);
};

const markBlockAsRecentlyUsed = (blockKey) => {
    const normalized = String(blockKey ?? '').trim();

    if (normalized === '') {
        return;
    }

    const availableKeys = registeredBlocks.map((item) => String(item?.key ?? '').trim());

    if (!availableKeys.includes(normalized)) {
        return;
    }

    recentBlockKeys.value = sanitizeRecentBlockKeys([normalized, ...recentBlockKeys.value], availableKeys);
    persistRecentBlockKeys();

    if (editor) {
        renderBlockManager(registeredBlocks, blockSearchTerm.value);
    }
};

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
        return true;
    }

    const slot = findFirstContentSlotComponent();

    if (!slot || slot === component) {
        if (typeof component.remove === 'function') {
            component.remove();
        }

        toast.warning(label('pb_drop_outside_content_warning', 'Blocks can only be dropped inside the content area.'));

        return false;
    }

    const collection = typeof slot.components === 'function' ? slot.components() : null;

    if (!collection || typeof collection.add !== 'function') {
        if (typeof component.remove === 'function') {
            component.remove();
        }

        toast.warning(label('pb_drop_outside_content_warning', 'Blocks can only be dropped inside the content area.'));

        return false;
    }

    relocatingComponent = true;
    collection.add(component);
    relocatingComponent = false;

    toast.info(label('pb_drop_moved_into_content', 'Block moved into the content area.'));

    lockToContentOnly();
    scheduleSync();

    return true;
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

const renderBlockManager = (blocks, searchTerm = '') => {
    if (!editor) {
        return;
    }

    const filteredBlocks = findMatchingBlocks(searchTerm, blocks);

    filteredBlockCount.value = filteredBlocks.length;

    const manager = editor.BlockManager;
    const categories = manager.getCategories();

    manager.getAll().reset();
    categories.reset();

    const quickCategoryId = 'pb-quick-blocks';
    const quickCategoryLabel = label('pb_quick_blocks', 'Quick blocks');
    const themeCategoryId = 'pb-theme-blocks';
    const themeCategoryLabel = label('pb_theme_blocks', 'Theme Blocks');

    const recentBlockSet = new Set(recentBlockKeys.value);
    const blockByKey = new Map(
        filteredBlocks.map((item) => [String(item?.key ?? '').trim(), item]),
    );
    const quickBlocks = recentBlockKeys.value
        .map((blockKey) => blockByKey.get(blockKey))
        .filter((item) => !!item);
    const remainingBlocks = filteredBlocks.filter((item) => !recentBlockSet.has(String(item?.key ?? '').trim()));

    if (quickBlocks.length > 0) {
        categories.add({
            id: quickCategoryId,
            label: quickCategoryLabel,
            open: true,
        });
    }

    categories.add({
        id: themeCategoryId,
        label: themeCategoryLabel,
        open: true,
    });

    quickBlocks.forEach((block) => {
        manager.add(`pagify-${block.key}`, {
            label: block.label,
            category: {
                id: quickCategoryId,
                label: quickCategoryLabel,
                open: true,
            },
            media: resolveBlockIconSvg(block),
            content: mapBlockContent(block),
            attributes: {
                title: block.description || block.label,
            },
        });
    });

    remainingBlocks.forEach((block) => {
        const isThemeBlock = primaryBlockKeySet.value.has(String(block?.key ?? ''));

        manager.add(`pagify-${block.key}`, {
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

    categories.each((category) => {
        const categoryId = category.get('id');
        category.set('open', categoryId === quickCategoryId || categoryId === themeCategoryId);
    });
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
    loadEditorPreferences();
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
            height: editorCanvasHeight.value,
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

        const sortedBlocks = [...props.blocks].sort((left, right) => {
            const leftTheme = primaryBlockKeySet.value.has(String(left?.key ?? '')) ? 0 : 1;
            const rightTheme = primaryBlockKeySet.value.has(String(right?.key ?? '')) ? 0 : 1;

            if (leftTheme !== rightTheme) {
                return leftTheme - rightTheme;
            }

            return String(left?.label ?? left?.key ?? '').localeCompare(String(right?.label ?? right?.key ?? ''));
        });
        registeredBlocks = sortedBlocks;
        buildBlockSearchIndex(registeredBlocks);
        loadRecentBlockKeys(registeredBlocks.map((item) => String(item?.key ?? '').trim()));
        renderBlockManager(registeredBlocks, blockSearchTerm.value);

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

        editor.on('update', () => {
            markEditorDirty();
            scheduleSync();
        });
        editor.on('block:drag:start', (blockModel) => {
            activeDraggedBlockKey = resolveBlockKeyFromBlockId(blockModel?.id);
        });
        editor.on('load', applyCurrentLayoutContext);
        editor.on('canvas:frame:load', applyCurrentLayoutContext);
        editor.on('component:mount', lockToContentOnly);
        editor.on('block:drag:stop', (component, blockModel) => {
            const isValidDrop = enforceDropInsideMain(component);

            if (!isValidDrop) {
                activeDraggedBlockKey = null;
                return;
            }

            const blockKey = resolveBlockKeyFromBlockId(blockModel?.id) ?? activeDraggedBlockKey;

            if (blockKey) {
                markBlockAsRecentlyUsed(blockKey);
            }

            activeDraggedBlockKey = null;
        });
        window.addEventListener('pbx-editor-flush', handleFlushRequest);
        window.addEventListener('keydown', handleGlobalKeydown);
        window.addEventListener('pbx-editor-search:set', handleExternalSearchEvent);
        applyEditorCanvasHeight();
        assessEditorHealth();
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
    window.removeEventListener('keydown', handleGlobalKeydown);
    window.removeEventListener('pbx-editor-search:set', handleExternalSearchEvent);
});

watch(selectedThemeLayout, (next, previous) => {
    if (!editor || next === '' || next === previous) {
        return;
    }

    applySelectedLayout();
});

watch(blockSearchTerm, (nextValue) => {
    if (!editor) {
        return;
    }

    renderBlockManager(registeredBlocks, nextValue);
});

watch(resolvedBlockSearchOptions, () => {
    if (!editor) {
        return;
    }

    buildBlockSearchIndex(registeredBlocks);
    renderBlockManager(registeredBlocks, blockSearchTerm.value);
});

watch(isFocusMode, () => {
    applyEditorCanvasHeight();
    persistEditorPreferences();
});
</script>

<template>
    <div class="space-y-3">
        <div v-if="!props.compactHeader" class="rounded border border-slate-200 bg-white p-3">
            <div class="flex flex-wrap items-center gap-2">
                <p class="text-xs text-slate-500">
                    {{ props.simpleMode
                        ? label('pb_editor_drag_drop_hint', 'Drag blocks from the right panel and drop directly into the canvas. Primary blocks are prioritized for quick use.')
                        : label('pb_editor_hint', 'GrapesJS canvas is active. Use the right block manager and top device switcher for responsive editing.') }}
                </p>
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] font-medium" :class="editorSyncClass">
                    {{ editorSyncLabel }}
                </span>
            </div>
            <div class="mt-2 flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center rounded border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    @click="isFocusMode = !isFocusMode"
                >
                    {{ isFocusMode
                        ? label('pb_focus_mode_exit', 'Exit focus mode')
                        : label('pb_focus_mode_enter', 'Focus mode') }}
                </button>
                <button
                    v-if="blockSearchTerm.trim() !== ''"
                    type="button"
                    class="inline-flex items-center rounded border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    @click="clearBlockSearch"
                >
                    {{ label('pb_clear_search', 'Clear search') }}
                </button>
                <button
                    v-if="recentBlockKeys.length > 0"
                    type="button"
                    class="inline-flex items-center rounded border border-slate-200 px-2 py-1 text-[11px] font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    @click="clearRecentBlocks"
                >
                    {{ label('pb_clear_quick_blocks', 'Clear quick blocks') }}
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                {{ label('pb_content_slot_hint', 'Only the highlighted content area is editable. Header and footer remain locked.') }}
            </p>
            <div class="mt-2 flex flex-wrap items-center gap-2 text-[11px]">
                <span class="inline-flex items-center rounded-full border px-2 py-0.5 font-medium" :class="editorHealthClass">
                    {{ editorHealthLabel }}
                </span>
                <span class="text-slate-600">
                    {{ label('pb_health_content_blocks', 'Content blocks') }}: {{ editorHealth.contentBlocks }}
                </span>
                <span class="text-slate-600">
                    {{ label('pb_health_outside_blocks', 'Outside blocks') }}: {{ editorHealth.outsideBlocks }}
                </span>
                <button
                    type="button"
                    class="inline-flex items-center rounded border border-slate-200 px-2 py-0.5 font-medium text-slate-600 transition hover:border-slate-300 hover:bg-slate-50"
                    @click="assessEditorHealth"
                >
                    {{ label('pb_health_rescan', 'Re-scan') }}
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-500">
                {{ label('pb_hotkey_hint', 'Shortcut: Ctrl/Cmd + S to sync changes quickly.') }}
            </p>
            <p v-if="lastSyncedAt" class="mt-1 text-[11px] text-slate-500">
                {{ label('pb_editor_last_synced', 'Last synced at') }}: {{ lastSyncedAt }}
            </p>
            <p v-if="activeTheme" class="mt-1 text-[11px] text-indigo-600">
                {{ label('pb_using_active_theme', 'Canvas is loaded with active theme styles:') }} {{ activeTheme }}
            </p>

            <div class="mt-2">
                <label class="sr-only" for="pb-block-search">{{ label('pb_block_search_placeholder', 'Search blocks...') }}</label>
                <input
                    id="pb-block-search"
                    v-model="blockSearchTerm"
                    type="search"
                    class="pf-input !py-1.5 text-sm"
                    :placeholder="label('pb_block_search_placeholder', 'Search blocks...')"
                >
                <p
                    v-if="blockSearchTerm.trim() !== '' && filteredBlockCount === 0"
                    class="mt-1 text-[11px] text-amber-700"
                >
                    {{ label('pb_no_blocks_match_search', 'No blocks match your search.') }}
                </p>
            </div>

            <div
                v-if="showEditorGuide"
                class="mt-3 rounded border border-indigo-200 bg-indigo-50 px-3 py-2 text-[11px] text-indigo-800"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="font-semibold">{{ label('pb_editor_quick_guide_title', 'Quick start guide') }}</p>
                    <button
                        type="button"
                        class="inline-flex items-center rounded border border-indigo-200 bg-white px-2 py-0.5 text-[10px] font-semibold text-indigo-700 hover:bg-indigo-100"
                        @click="dismissEditorGuide"
                    >
                        {{ label('pb_editor_quick_guide_dismiss', 'Got it') }}
                    </button>
                </div>
                <ol class="mt-1 list-decimal space-y-0.5 pl-4">
                    <li>{{ label('pb_editor_quick_guide_step_1', 'Pick a block from Theme/Quick blocks on the right panel.') }}</li>
                    <li>{{ label('pb_editor_quick_guide_step_2', 'Drop it into the highlighted content area.') }}</li>
                    <li>{{ label('pb_editor_quick_guide_step_3', 'Use Save changes when sync status turns green.') }}</li>
                </ol>
            </div>
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
