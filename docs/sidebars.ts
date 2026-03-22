import type {SidebarsConfig} from '@docusaurus/plugin-content-docs';

// This runs in Node.js - Don't use client-side code here (browser APIs, JSX...)

/**
 * Creating a sidebar enables you to:
 - create an ordered group of docs
 - render a sidebar for each doc of that group
 - provide next/previous navigation

 The sidebars can be generated from the filesystem, or explicitly defined here.

 Create as many sidebars as you want.
 */
const sidebars: SidebarsConfig = {
  docsSidebar: [
    'intro',
    {
      type: 'category',
      label: 'Getting Started',
      items: [
        'getting-started/quickstart',
        'getting-started/learning-path',
      ],
    },
    {
      type: 'category',
      label: 'Architecture',
      items: [
        'architecture/how-pagify-works',
        'overview/features-capabilities',
        'architecture/system-overview',
      ],
    },
    {
      type: 'category',
      label: 'Module Guides',
      items: [
        'modules/core-module',
        'modules/media-module',
        'modules/page-builder-module',
        'modules/updater-module',
      ],
    },
    {
      type: 'category',
      label: 'User Guides',
      items: [
        'guides/admin-operations-guide',
        'guides/custom-component-quickstart',
      ],
    },
    {
      type: 'category',
      label: 'Extensibility',
      items: [
        'admin/admin-ui-primitives',
        'guides/advanced/admin-ui-extension',
        'guides/advanced/theme-development',
        'guides/advanced/theme-customization',
        'guides/advanced/plugin-development',
      ],
    },
    {
      type: 'category',
      label: 'Operations',
      items: [
        'operations/installation-and-deployment',
        'operations/runbook',
      ],
    },
    {
      type: 'category',
      label: 'Reference',
      items: [
        'reference/api-surface',
        'reference/artisan-commands',
        'reference/theme-development-contract',
      ],
    },
  ],
};

export default sidebars;
