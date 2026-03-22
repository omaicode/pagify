---
sidebar_position: 4
title: Page Builder Module
---

# Page Builder Module

The Page Builder module provides admin tooling for composing, saving, and publishing pages with an embedded Webstudio client.

## Responsibilities

- Page CRUD in the admin interface
- Embedded editor shell for page composition
- Persisted page-builder UI state per page
- Media asset upload and deletion from the editor
- Publish and draft state management per page
- Registry, contract, and compatibility endpoints required by Webstudio

## Current integration model

The current integration no longer uses an external iframe host as the primary editor runtime.

- Laravel serves a module-owned SPA shell at `/{admin_prefix}/page-builder/editor-spa/{path?}`.
- The shell view is `modules/page-builder/resources/views/editor-spa.blade.php`.
- The shell loads the built Webstudio client through Laravel Vite:
  - `@vite('resources/js/webstudio-vite-entry.js', 'build/page-builder')`
- The Webstudio client is built inside the module and copied to Laravel public assets.
- Admin `Pages` opens this SPA route, and Webstudio bootstraps from `window.__pagifyWebstudioBootstrap`.

## Key admin routes

- `/{admin_prefix}/page-builder/pages`
- `/{admin_prefix}/page-builder/pages/{page}/preview`
- `/{admin_prefix}/page-builder/editor-spa/{path?}`

## Key API groups

Primary admin APIs:

- `api/v1/{admin_prefix}/page-builder/editor/access-token`
- `api/v1/{admin_prefix}/page-builder/pages`
- `api/v1/{admin_prefix}/page-builder/pages/{page}`
- `api/v1/{admin_prefix}/page-builder/pages/{page}/publish`
- `api/v1/{admin_prefix}/page-builder/folders`
- `api/v1/{admin_prefix}/page-builder/folders/{folderId}`
- `api/v1/{admin_prefix}/page-builder/folders/move`

Webstudio compatibility APIs:

- `GET api/v1/{admin_prefix}/page-builder/data/{projectId}`
- `POST api/v1/{admin_prefix}/page-builder/patch`
- `POST api/v1/{admin_prefix}/page-builder/resources-loader`
- `GET|POST api/v1/{admin_prefix}/page-builder/assets`
- `POST api/v1/{admin_prefix}/page-builder/assets/{name}`
- `DELETE api/v1/{admin_prefix}/page-builder/assets/{assetId}`
- `GET|POST api/v1/{admin_prefix}/page-builder/trpc/{path?}`
- `POST api/v1/{admin_prefix}/page-builder/dashboard-logout`

Webstudio asset proxy endpoints:

- `GET /cgi/image/{path?}`
- `GET /cgi/asset/{path?}`

## Data model notes

`projectId` in the compatibility layer maps to the current Pagify page id.

- Each page has its own `PageBuilderState` row.
- `PATCH` persistence is page-scoped, not global project-scoped.
- `data_json` stores only persisted UI state required to rebuild the editor surface.
- Dynamic data such as page tree metadata and current publish status should come from live server data, not from the persisted snapshot.
- Folder tree is persisted in a site-scoped folder domain, and page-folder assignment is stored per page.

## `GET /data/{projectId}` behavior

The compatibility data endpoint is the source of truth for bootstrapping Webstudio for a specific page.

- Returns `id`, `version`, and `projectId` for the selected page state.
- Returns persisted design state such as `instances`, `props`, `styles`, `styleSources`, `styleSourceSelections`, `resources`, and `dataSources`.
- Returns page tree metadata from the current database state so page title, slug, and publish state stay fresh when switching pages.
- Returns folder tree from server-authoritative folder domain (including nested folders and ordered children).
- Returns media assets mapped from the Pagify media system.

## Folder APIs behavior

Page folders are managed through dedicated APIs instead of local-only editor state.

- `GET /folders` returns folder tree payload for editor synchronization.
- `POST /folders` creates a folder in current site scope.
- `PUT /folders/{folderId}` updates folder metadata or parent.
- `POST /folders/move` supports reorder/reparent for both folders and pages.
- `DELETE /folders/{folderId}` removes folder and reparents page children to root.

## `POST /patch` behavior

The compatibility patch endpoint persists changes for the currently selected page.

- Client sends transaction batch plus a reduced `state` snapshot.
- Snapshot should include only persisted UI state required for editor reconstruction.
- Frequently changing dynamic data should not be embedded into the snapshot if it already exists in server APIs.
- Versioning is optimistic and checked per page state record.

## Assets behavior

The editor asset manager is backed by the Pagify media system.

- Uploading an asset stores it in the media library and returns Webstudio-compatible asset payloads.
- Deleting an asset from Webstudio also deletes the corresponding system asset.
- Asset `name` is returned as a relative path so Webstudio image URLs resolve cleanly through `/cgi/image/...`.
- `/cgi/image/*` and `/cgi/asset/*` exist as compatibility proxies for Webstudio loaders.

## Publish behavior

Publish state is controlled per page.

- The Publish switch must reflect the currently selected page.
- Switching pages should reload publish state for that page from live server data.
- Persisted builder snapshots must not override live publish status from the database.

## Lifecycle

1. Open `Pages` in admin.
2. Laravel serves the Webstudio SPA shell.
3. Webstudio boots with page-scoped compatibility data from `/data/{projectId}`.
4. User edits the selected page.
5. Webstudio mutates page/folder tree through folder/page APIs.
6. Webstudio saves page-scoped UI state through `/patch`.
7. User toggles draft or publish state through page CRUD APIs.

## Admin fullscreen

Admin editor shell provides a fullscreen toggle for easier editing.

- Uses browser Fullscreen API when available.
- Falls back to opening editor URL in a new full window/tab when Fullscreen API is unavailable.

## Operational notes

- Rebuild and publish the Webstudio client whenever the editor frontend changes.
- Keep the Vite output namespace consistent with `build/page-builder`.
- If the editor loads but stays at loading state, verify iframe embedding, canvas sync, and the compatibility `/data` response.
- If thumbnails fail, inspect `/cgi/image/*` responses and returned asset `name` values.
- If page edits save to the wrong record, verify the selected page id is also the `projectId` sent to `/patch`.

## Testing coverage highlights

- page builder lifecycle
- embedded Webstudio shell bootstrap
- page-scoped compatibility data
- asset upload and delete compatibility
- publish state hydration from live database state

## Register Component for Webstudio

Page Builder supports component registration from both module and plugin by using class-based component definitions.

Direct quickstart guide:

- [CustomComponent Quickstart](../guides/custom-component-quickstart)

### Discovery flow

1. Page Builder subscribes hook `page-builder.webstudio.components` on Event Bus.
2. Discovery service scans enabled modules/plugins for class references in the main config.
3. Definitions are normalized and validated by `ComponentDefinitionDiscoveryService` + `ComponentDefinitionValidator` with owner metadata.
4. Registry endpoint returns owner-aware blocks.
5. Compatibility `GET /data/{projectId}` returns `registeredComponents` for editor bootstrap.
6. Webstudio Components tab groups registered items by owner (module/plugin).

### Module main config

Add `webstudio_components` in `modules/{module-slug}/config/module.php`:

```php
<?php

use Vendor\YourModule\Webstudio\Components\HeroBannerComponent;

return [
  // ...existing module config
  'webstudio_components' => [
    HeroBannerComponent::class,
  ],
];
```
### Plugin main config

Create `plugins/{plugin-slug}/config/plugin.php` and declare `webstudio_components`:

```php
<?php

use Plugins\DemoWebstudioRegister\Webstudio\Components\CtaStripComponent;

return [
  'webstudio_components' => [
    CtaStripComponent::class,
  ],
];
```

Each component class must implement `Pagify\PageBuilder\Webstudio\Contracts\CustomComponent` and return a definition array.
Dynamic fallback via arbitrary `definition()` objects is not supported.

To reduce verbosity and avoid missing fields, you can use `Pagify\PageBuilder\Webstudio\Support\ComponentDefinitionBuilder`:

```php
<?php

use Pagify\PageBuilder\Webstudio\Support\ComponentDefinitionBuilder;

return ComponentDefinitionBuilder::make('hero-banner', 'Hero Banner')
  ->description('Starter hero block')
  ->element('section')
  ->classes(['hero', 'hero--primary'])
  ->styles([
    'padding' => '24px',
    'border-radius' => '12px',
  ])
  ->attribute('data-variant', 'hero')
  ->text('Hero Banner')
  ->toArray();
```

### Supported fields

- `key` (required): unique component key in owner scope
- `label` (optional): display name in Components tab
- `description` (optional): tooltip/description text
- `icon` (optional): icon string
- `category` (optional): legacy category metadata
- `owner` (optional): module/plugin slug for grouping
- `owner_type` (optional): `module` or `plugin`
- `html_template` (optional): fallback snippet for legacy block rendering
- `element` or `tag` (optional): HTML element name used when `html_template` is omitted
- `class` (optional): string or array of css classes
- `style` (optional): inline style string or key/value array
- `attributes` (optional): HTML attributes map (`data-*`, `role`, etc.)
- `text` or `inner_html` (optional): inner content for generated template
- `children` (optional): nested child nodes/components for template composition
- `dynamic_data` (optional): dynamic context payload for server-side pre-render in editor APIs
- `props_schema` (optional): custom props metadata

`children` supports:

- string: references another registered component key (for example `hero-banner` or `demo-webstudio-register:hero-banner`)
- object node: supports `key`/`component` reference, or inline `element/tag` + `class` + `style` + `attributes` + `text` + `inner_html` + recursive `children`

Example:

```php
return ComponentDefinitionBuilder::make('cta-strip', 'CTA Strip')
  ->tag('div')
  ->classes('cta-strip')
  ->children([
    'hero-banner',
    [
      'element' => 'p',
      'text' => 'Nested note',
    ],
  ])
  ->toArray();
```

### Dynamic data pre-render before editor

Custom component definitions support server-side placeholder rendering before payload is sent to Webstudio editor.

- Placeholder format: `{{ page.title }}`, `{{ page.slug }}`, `{{ dynamic.summary }}`, `{{ now }}`
- Rendering is applied in both:
  - `GET /api/v1/{admin_prefix}/page-builder/data/{projectId}`
- Target fields are rendered before editor bootstrap: `label`, `description`, `html_template`, `text`, `inner_html`, `class`, `style`, attribute values, nested `children`, and `dynamic_data`.

Supported placeholder namespaces (via context helper):

- `page.*`
- `project.*`
- `runtime.*`
- `dynamic.*`
- `now`

Fail-fast validation:

- Invalid placeholder root (for example `{{ foo.bar }}`) makes the component definition invalid and it will be skipped.
- Invalid/malformed placeholder syntax (for example missing `}}`) makes the component definition invalid and it will be skipped.
- `dynamic.*` must reference an existing key in `dynamic_data`; unknown paths are rejected.

Example:

```php
return ComponentDefinitionBuilder::make('hero-banner', 'Hero Banner')
  ->dynamicData([
    'summary' => 'Page: {{ page.title }}',
  ])
  ->description('Dynamic summary: {{ dynamic.summary }}')
  ->attribute('data-page', '{{ page.slug }}')
  ->text('Welcome to {{ page.title }}')
  ->toArray();
```

### Normalization rules

- If `owner` is missing, owner defaults to module/plugin slug.
- If `key` has no namespace, key is prefixed as `{owner}:{key}`.
- If `html_template` is missing and `element/tag` is provided, template is generated from element + class/style/attributes.
- If neither `html_template` nor `element/tag` is provided, a default section template is generated.
- Invalid component definitions (missing/invalid `key`) are skipped before registry/data payload.
- Missing optional fields are auto-normalized (`label`, `icon`, `category`, `owner_type`, `attributes`, `props_schema`).

### End-to-end verification checklist

1. Add or update component class and register it in module/plugin main config.
2. Ensure module/plugin is enabled.
3. Open `GET /api/v1/{admin_prefix}/page-builder/data/{projectId}` and verify `registeredComponents` include `owner`, `owner_type`, `source`, and normalized `key`.
4. Open `GET /api/v1/{admin_prefix}/page-builder/data/{projectId}` and verify `registeredComponents` payload.
5. Open editor and confirm Components tab shows a group named by owner with registered items.

### CI validation command

Run command below in CI to fail early when `webstudio_components` has invalid classes or invalid definitions:

```bash
php artisan cms:page-builder:validate-webstudio-components
```

Optional machine-readable output:

```bash
php artisan cms:page-builder:validate-webstudio-components --json
```
