import {Fragment, useEffect, useMemo, useState} from 'react';
import Layout from '@theme/Layout';
import Heading from '@theme/Heading';
import useBaseUrl from '@docusaurus/useBaseUrl';

import styles from './api-reference.module.css';

type ApiMethod = {
  name: string;
  description?: string;
};

type ApiEntry = {
  module: string;
  layer: string;
  className: string;
  description?: string;
  file: string;
  methods: ApiMethod[];
};

type ApiIndex = {
  generatedAt: string;
  totalClasses: number;
  entries: ApiEntry[];
};

const normalizeIndex = (payload: ApiIndex): ApiIndex => {
  return {
    ...payload,
    entries: payload.entries.map((entry) => ({
      ...entry,
      methods: (entry.methods as unknown[]).map((method) => {
        if (typeof method === 'string') {
          return {name: method, description: ''};
        }

        const m = method as ApiMethod;
        return {name: m.name, description: m.description || ''};
      }),
    })),
  };
};

const moduleLabel = (moduleName: string): string => {
  switch (moduleName) {
    case 'page-builder':
      return 'Page Builder';
    default:
      return moduleName.charAt(0).toUpperCase() + moduleName.slice(1);
  }
};

const layerBadgeClass = (layer: string): string => {
  const value = layer.toLowerCase();

  if (value.includes('controllers')) {
    return styles.badgeController;
  }

  if (value.includes('services')) {
    return styles.badgeService;
  }

  if (value.includes('models')) {
    return styles.badgeModel;
  }

  if (value.includes('policies')) {
    return styles.badgePolicy;
  }

  if (value.includes('requests')) {
    return styles.badgeRequest;
  }

  if (value.includes('resources')) {
    return styles.badgeResource;
  }

  if (value.includes('console')) {
    return styles.badgeCommand;
  }

  if (value.includes('jobs')) {
    return styles.badgeJob;
  }

  return styles.badgeDefault;
};

export default function ApiReferencePage() {
  const dataUrl = useBaseUrl('/api-reference/index.json');

  const [index, setIndex] = useState<ApiIndex | null>(null);
  const [query, setQuery] = useState('');
  const [moduleFilter, setModuleFilter] = useState('all');
  const [layerFilter, setLayerFilter] = useState('all');
  const [expanded, setExpanded] = useState<Record<string, boolean>>({});
  const [copiedKey, setCopiedKey] = useState('');

  const copyText = async (key: string, text: string) => {
    try {
      await navigator.clipboard.writeText(text);
      setCopiedKey(key);
      setTimeout(() => setCopiedKey(''), 1200);
    } catch {
      setCopiedKey('');
    }
  };

  useEffect(() => {
    let mounted = true;

    fetch(dataUrl)
      .then((res) => res.json())
      .then((payload: ApiIndex) => {
        if (mounted) {
          setIndex(normalizeIndex(payload));
        }
      })
      .catch(() => {
        if (mounted) {
          setIndex({generatedAt: '', totalClasses: 0, entries: []});
        }
      });

    return () => {
      mounted = false;
    };
  }, [dataUrl]);

  const moduleOptions = useMemo(() => {
    if (!index) {
      return [];
    }

    return [...new Set(index.entries.map((item) => item.module))];
  }, [index]);

  const layerOptions = useMemo(() => {
    if (!index) {
      return [];
    }

    const scope = moduleFilter === 'all' ? index.entries : index.entries.filter((item) => item.module === moduleFilter);
    return [...new Set(scope.map((item) => item.layer))];
  }, [index, moduleFilter]);

  const filtered = useMemo(() => {
    if (!index) {
      return [];
    }

    const q = query.trim().toLowerCase();

    return index.entries.filter((item) => {
      if (moduleFilter !== 'all' && item.module !== moduleFilter) {
        return false;
      }

      if (layerFilter !== 'all' && item.layer !== layerFilter) {
        return false;
      }

      if (!q) {
        return true;
      }

      const classHit = item.className.toLowerCase().includes(q);
      const methodHit = item.methods.some((method) => method.name.toLowerCase().includes(q));
      const fileHit = item.file.toLowerCase().includes(q);
      const descHit = (item.description || '').toLowerCase().includes(q);

      return classHit || methodHit || fileHit || descHit;
    });
  }, [index, query, moduleFilter, layerFilter]);

  return (
    <Layout title="API Reference" description="Searchable Pagify class and function reference by module.">
      <main className={styles.page}>
        <div className="container">
          <Heading as="h1">API Reference</Heading>
          <p>
            Standalone class and function index, organized by module and layer. Use the filters below to find classes
            or methods quickly.
          </p>

          <div className={styles.toolbar}>
            <input
              className={styles.input}
              placeholder="Search class or function (for example: publish, AdminThemeController)"
              value={query}
              onChange={(event) => setQuery(event.target.value)}
            />
            <select
              className={styles.select}
              value={moduleFilter}
              onChange={(event) => {
                setModuleFilter(event.target.value);
                setLayerFilter('all');
              }}>
              <option value="all">All modules</option>
              {moduleOptions.map((moduleName) => (
                <option key={moduleName} value={moduleName}>
                  {moduleLabel(moduleName)}
                </option>
              ))}
            </select>
            <select className={styles.select} value={layerFilter} onChange={(event) => setLayerFilter(event.target.value)}>
              <option value="all">All layers</option>
              {layerOptions.map((layer) => (
                <option key={layer} value={layer}>
                  {layer}
                </option>
              ))}
            </select>
          </div>

          <p className={styles.summary}>
            {index ? `${filtered.length} / ${index.totalClasses} classes` : 'Loading API index...'}
          </p>

          {index && filtered.length === 0 ? (
            <div className={styles.empty}>No classes or methods match your search/filter.</div>
          ) : (
            <div className={styles.tableWrap}>
              <table className={styles.table}>
                <thead>
                  <tr>
                    <th>Module</th>
                    <th>Class Info</th>
                    <th>Functions</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filtered.map((item) => (
                    <Fragment key={`${item.file}:${item.className}`}>
                      <tr>
                        <td>{moduleLabel(item.module)}</td>
                        <td>
                          <div className={styles.infoHead}>
                            <div className={styles.className}>{item.className}</div>
                            <button
                              className={styles.iconCopyBtn}
                              type="button"
                              aria-label={`Copy class ${item.className}`}
                              onClick={() => copyText(`${item.className}:class`, item.className)}>
                              {copiedKey === `${item.className}:class` ? '✔' : '⧉'}
                            </button>
                          </div>
                          <div className={styles.meta}>
                            <strong>Layer:</strong>{' '}
                            <span className={`${styles.layerBadge} ${layerBadgeClass(item.layer)}`}>{item.layer}</span>
                          </div>
                          <div className={styles.infoHead}>
                            <div className={styles.meta}><strong>Source:</strong></div>
                            <button
                              className={styles.iconCopyBtn}
                              type="button"
                              aria-label={`Copy source ${item.file}`}
                              onClick={() => copyText(`${item.className}:source`, item.file)}>
                              {copiedKey === `${item.className}:source` ? '✔' : '⧉'}
                            </button>
                          </div>
                          <div className={`${styles.meta} ${styles.path} ${styles.sourcePath}`}>{item.file}</div>
                        </td>
                        <td>
                          {item.methods.length > 0 ? (
                            <ul className={styles.methodList}>
                              {item.methods.map((method) => (
                                <li key={`${item.className}:${method.name}`}>{method.name}</li>
                              ))}
                            </ul>
                          ) : (
                            'No public functions'
                          )}
                        </td>
                        <td>
                          <div className={styles.actions}>
                            <button
                              className={styles.detailBtn}
                              type="button"
                              onClick={() =>
                                setExpanded((prev) => ({
                                  ...prev,
                                  [item.file]: !prev[item.file],
                                }))
                              }>
                              {expanded[item.file] ? 'Hide Details' : 'View Details'}
                            </button>
                          </div>
                        </td>
                      </tr>
                      {expanded[item.file] && (
                        <tr key={`${item.file}:details`}>
                          <td colSpan={4}>
                            <div className={styles.detailPanel}>
                              <div>
                                <strong>Class Description:</strong> {item.description || 'No description available.'}
                              </div>
                              <div className={styles.detailTitle}>Function Details</div>
                              {item.methods.length > 0 ? (
                                <ul className={styles.methodDetails}>
                                  {item.methods.map((method) => (
                                    <li key={`${item.className}:detail:${method.name}`}>
                                      <div className={styles.methodHead}>
                                        <span className={styles.methodName}>{method.name}</span>
                                        <button
                                          className={styles.inlineCopyBtn}
                                          type="button"
                                          onClick={() => copyText(`${item.className}:${method.name}`, method.name)}>
                                          {copiedKey === `${item.className}:${method.name}` ? 'Copied' : 'Copy'}
                                        </button>
                                      </div>
                                      <div className={styles.methodDescription}>
                                        {method.description || 'No description available.'}
                                      </div>
                                    </li>
                                  ))}
                                </ul>
                              ) : (
                                <div>No public functions available.</div>
                              )}
                            </div>
                          </td>
                        </tr>
                      )}
                    </Fragment>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </Layout>
  );
}
