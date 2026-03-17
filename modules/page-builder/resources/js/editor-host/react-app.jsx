import React, { useEffect, useMemo, useRef, useState } from 'react';
import { createRoot } from 'react-dom/client';

const appRoots = new WeakMap();

const PRESET_BLOCKS = [
    {
        id: 'hero',
        label: 'Hero section',
        markup: '<section class="pbx-hero"><h1>Build pages faster</h1><p>Compose blocks, edit styles, and publish instantly.</p><a class="pbx-button" href="#">Get started</a></section>',
    },
    {
        id: 'feature-grid',
        label: 'Feature grid',
        markup: '<section class="pbx-grid"><article><h3>Drag blocks</h3><p>Insert predefined sections in one click.</p></article><article><h3>Live preview</h3><p>See updates in the center canvas immediately.</p></article><article><h3>Media bridge</h3><p>Upload and attach images without leaving editor.</p></article></section>',
    },
    {
        id: 'cta',
        label: 'Call to action',
        markup: '<section class="pbx-cta"><h2>Ready to publish?</h2><p>Review changes then push to parent editor.</p><a class="pbx-button" href="#">Publish now</a></section>',
    },
];

const ui = {
    page: {
        fontFamily: 'ui-sans-serif, system-ui, sans-serif',
        minHeight: '100vh',
        color: '#0f172a',
        background: 'linear-gradient(160deg, #f8fafc 0%, #eef2ff 45%, #f1f5f9 100%)',
        padding: '14px',
    },
    title: { margin: 0, fontSize: '18px', fontWeight: 700 },
    subtitle: { margin: '4px 0 0', fontSize: '12px', color: '#475569' },
    toolbar: {
        display: 'flex',
        flexWrap: 'wrap',
        gap: '8px',
        marginTop: '10px',
        alignItems: 'center',
    },
    chip: {
        border: '1px solid #cbd5e1',
        borderRadius: '999px',
        background: '#ffffff',
        padding: '4px 10px',
        fontSize: '11px',
        color: '#334155',
    },
    layout: {
        display: 'grid',
        gridTemplateColumns: '260px minmax(0, 1fr) 340px',
        gap: '12px',
        marginTop: '12px',
        alignItems: 'start',
    },
    panel: {
        border: '1px solid #dbe2f0',
        borderRadius: '12px',
        background: '#ffffff',
        boxShadow: '0 6px 24px rgba(15, 23, 42, 0.06)',
        padding: '12px',
    },
    panelTitle: { margin: 0, fontSize: '13px', fontWeight: 700 },
    muted: { margin: '4px 0 0', fontSize: '11px', color: '#64748b' },
    button: {
        border: '1px solid #2563eb',
        color: '#1d4ed8',
        background: '#eff6ff',
        borderRadius: '8px',
        padding: '7px 10px',
        fontSize: '12px',
        cursor: 'pointer',
    },
    buttonSecondary: {
        border: '1px solid #cbd5e1',
        color: '#334155',
        background: '#f8fafc',
        borderRadius: '8px',
        padding: '7px 10px',
        fontSize: '12px',
        cursor: 'pointer',
    },
    input: {
        border: '1px solid #cbd5e1',
        borderRadius: '8px',
        padding: '7px 9px',
        fontSize: '12px',
        width: '100%',
        boxSizing: 'border-box',
    },
    textarea: {
        width: '100%',
        minHeight: '180px',
        border: '1px solid #cbd5e1',
        borderRadius: '10px',
        padding: '10px',
        fontFamily: 'ui-monospace, SFMono-Regular, Menlo, monospace',
        fontSize: '12px',
        boxSizing: 'border-box',
    },
};

const EditorHostApp = ({ state, actions }) => {
    const [mediaQuery, setMediaQuery] = useState(state.media.query ?? '');
    const [htmlDraft, setHtmlDraft] = useState('');
    const [cssDraft, setCssDraft] = useState('');
    const [autoFlush, setAutoFlush] = useState(false);
    const [showJson, setShowJson] = useState(false);
    const mountRef = useRef(null);

    useEffect(() => {
        setMediaQuery(state.media.query ?? '');
    }, [state.media.query]);

    useEffect(() => {
        const webstudio = state.layout?.webstudio ?? {};
        setHtmlDraft(String(webstudio.html ?? ''));
        setCssDraft(String(webstudio.css ?? ''));
    }, [state.layout]);

    useEffect(() => {
        actions.mountWebstudio(mountRef.current);

        return () => {
            actions.unmountWebstudio();
        };
    }, [actions]);

    useEffect(() => {
        actions.updateWebstudio();
    }, [actions, state.layout]);

    const mediaAssets = useMemo(() => (Array.isArray(state.media.assets) ? state.media.assets : []), [state.media.assets]);
    const runtimeMode = String(state.webstudio.runtimeMode ?? 'dom-preview');
    const adapterStatus = String(state.webstudio.status ?? 'idle');

    const handleApplyMarkup = () => {
        actions.onMarkupChange(htmlDraft, cssDraft, autoFlush);
    };

    return (
        <section style={ui.page} data-phase="3" className="pbx-host-page">
            <h2 style={ui.title}>Pagify Full Builder Workspace</h2>
            <p style={ui.subtitle}>
                mode={String(state.boot.mode ?? 'page-builder')} theme={String(state.boot.theme ?? '')}
            </p>

            <div style={ui.toolbar}>
                <span style={ui.chip}>runtime={runtimeMode}</span>
                <span style={ui.chip}>status={adapterStatus}</span>
                <span style={ui.chip}>mounted={String(Boolean(state.webstudio.mounted))}</span>
                <span style={ui.chip}>media={mediaAssets.length}</span>
                <button type="button" style={ui.button} onClick={handleApplyMarkup}>Apply to canvas</button>
                <button type="button" style={ui.button} onClick={() => actions.onFlush()}>Flush to parent</button>
                <button type="button" style={ui.buttonSecondary} onClick={() => setShowJson((value) => !value)}>
                    {showJson ? 'Hide raw JSON' : 'Show raw JSON'}
                </button>
                <label style={{ display: 'inline-flex', alignItems: 'center', gap: '6px', fontSize: '12px', color: '#334155' }}>
                    <input type="checkbox" checked={autoFlush} onChange={(event) => setAutoFlush(event.target.checked)} />
                    Auto flush when apply/insert
                </label>
            </div>

            <div style={ui.layout} className="pbx-host-grid">
                <aside style={ui.panel}>
                    <h3 style={ui.panelTitle}>Block presets</h3>
                    <p style={ui.muted}>One-click sections to speed up page composition.</p>
                    <div style={{ display: 'grid', gap: '8px', marginTop: '10px' }}>
                        {PRESET_BLOCKS.map((preset) => (
                            <button
                                key={preset.id}
                                type="button"
                                style={ui.buttonSecondary}
                                onClick={() => actions.onInsertPreset(preset.markup, autoFlush)}
                            >
                                + {preset.label}
                            </button>
                        ))}
                    </div>

                    <h3 style={{ ...ui.panelTitle, marginTop: '16px' }}>Media library</h3>
                    <p style={ui.muted}>{state.media.status}</p>
                    <div style={{ display: 'grid', gap: '8px', marginTop: '8px' }}>
                        <input
                            type="search"
                            value={mediaQuery}
                            onChange={(event) => setMediaQuery(event.target.value)}
                            placeholder="Search media..."
                            style={ui.input}
                        />
                        <button type="button" style={ui.buttonSecondary} onClick={() => actions.onMediaSearch(mediaQuery)}>Load media</button>
                        <input type="file" onChange={(event) => actions.onMediaFileSelected(event.target.files?.[0] ?? null)} style={{ fontSize: '12px' }} />
                    </div>

                    <div style={{ marginTop: '10px', display: 'grid', gap: '8px', maxHeight: '380px', overflow: 'auto' }}>
                        {mediaAssets.map((asset) => (
                            <article key={asset.id ?? asset.uuid ?? asset.path} style={{ border: '1px solid #e2e8f0', borderRadius: '8px', padding: '8px' }}>
                                <div style={{ fontSize: '12px', fontWeight: 600 }}>{String(asset.original_name ?? asset.filename ?? 'asset')}</div>
                                <div style={{ fontSize: '10px', color: '#64748b', margin: '4px 0 6px', wordBreak: 'break-all' }}>{String(asset.path ?? '')}</div>
                                <button type="button" style={ui.buttonSecondary} onClick={() => actions.onMediaSelect(asset)}>Insert ref</button>
                            </article>
                        ))}
                    </div>
                </aside>

                <main style={ui.panel}>
                    <h3 style={ui.panelTitle}>Live canvas</h3>
                    <p style={ui.muted}>{String(state.webstudio.message ?? '')}</p>
                    {Array.isArray(state.webstudio.warnings) && state.webstudio.warnings.length > 0 ? (
                        <ul style={{ margin: '8px 0 10px', paddingLeft: '18px', color: '#b45309', fontSize: '11px' }}>
                            {state.webstudio.warnings.map((warning) => (
                                <li key={warning}>{String(warning)}</li>
                            ))}
                        </ul>
                    ) : null}
                    <div ref={mountRef} style={{ marginTop: '8px' }} />
                </main>

                <aside style={ui.panel}>
                    <h3 style={ui.panelTitle}>Inspector</h3>
                    <p style={ui.muted}>Edit markup and style directly, then apply.</p>

                    <label style={{ display: 'block', marginTop: '10px', marginBottom: '6px', fontSize: '12px', fontWeight: 600 }}>HTML</label>
                    <textarea
                        value={htmlDraft}
                        onChange={(event) => setHtmlDraft(event.target.value)}
                        style={{ ...ui.textarea, minHeight: '170px' }}
                    />

                    <label style={{ display: 'block', marginTop: '10px', marginBottom: '6px', fontSize: '12px', fontWeight: 600 }}>CSS</label>
                    <textarea
                        value={cssDraft}
                        onChange={(event) => setCssDraft(event.target.value)}
                        style={{ ...ui.textarea, minHeight: '150px' }}
                    />

                    <div style={{ marginTop: '10px', display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
                        <button type="button" style={ui.button} onClick={handleApplyMarkup}>Apply changes</button>
                        <button
                            type="button"
                            style={ui.buttonSecondary}
                            onClick={() => {
                                const webstudio = state.layout?.webstudio ?? {};
                                setHtmlDraft(String(webstudio.html ?? ''));
                                setCssDraft(String(webstudio.css ?? ''));
                            }}
                        >
                            Reset draft
                        </button>
                    </div>
                </aside>
            </div>

            {showJson ? (
                <section style={{ ...ui.panel, marginTop: '12px' }}>
                    <h3 style={ui.panelTitle}>Advanced raw JSON</h3>
                    <p style={ui.muted}>Fallback mode for direct layout payload editing.</p>
                    <textarea
                        value={JSON.stringify(state.layout, null, 2)}
                        onChange={(event) => actions.onLayoutInput(event.target.value)}
                        style={{ ...ui.textarea, minHeight: '220px', marginTop: '8px' }}
                    />
                </section>
            ) : null}

            <style>
                {`@media (max-width: 1180px) {
                    .pbx-host-page { padding: 10px; }
                    .pbx-host-grid {
                        grid-template-columns: 1fr;
                    }
                }`}
            </style>
        </section>
    );
};

export const renderEditorHostReactApp = (container, props) => {
    if (!container) {
        return;
    }

    let root = appRoots.get(container);

    if (!root) {
        root = createRoot(container);
        appRoots.set(container, root);
    }

    root.render(<EditorHostApp {...props} />);
};

export const unmountEditorHostReactApp = (container) => {
    if (!container) {
        return;
    }

    const root = appRoots.get(container);

    if (root) {
        root.unmount();
        appRoots.delete(container);
    }
};
