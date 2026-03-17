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
      label: 'Overview',
      items: [
        'overview/features-capabilities',
        'architecture/system-overview',
      ],
    },
    {
      type: 'category',
      label: 'Module Guides',
      items: [
        'modules/core-module',
        'modules/content-module',
        'modules/media-module',
        'modules/page-builder-module',
        'modules/updater-module',
      ],
    },
    {
      type: 'category',
      label: 'User Guides',
      items: [
        'guides/content-authoring-guide',
        'guides/admin-operations-guide',
      ],
    },
    {
      type: 'category',
      label: 'Advanced Guides',
      items: [
        'guides/advanced/admin-ui-extension',
        'guides/advanced/theme-customization',
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
        'admin/admin-ui-primitives',
        'themes/theme-development-contract',
      ],
    },
  ],
};

export default sidebars;
