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

- `api/v1/{admin_prefix}/page-builder/registry`
- `api/v1/{admin_prefix}/page-builder/editor/access-token`
- `api/v1/{admin_prefix}/page-builder/editor/verify-token`
- `api/v1/{admin_prefix}/page-builder/editor/contract`
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

Page Builder supports component registration from both module and plugin by using PHP definition files.

### Discovery flow

1. Page Builder subscribes hook `page-builder.webstudio.components` on Event Bus.
2. Discovery service scans enabled modules/plugins for definition files.
3. Definitions are normalized by `BlockRegistryService` with owner metadata.
4. Registry endpoint returns owner-aware blocks.
5. Compatibility `GET /data/{projectId}` returns `registeredComponents` for editor bootstrap.
6. Webstudio Components tab groups registered items by owner (module/plugin).

### Module definition file

Create file at `modules/{module-slug}/config/webstudio-components.php`.

```php
<?php

return [
  [
    'key' => 'hero-banner',
    'label' => 'Hero Banner',
    'description' => 'Starter hero section for landing pages.',
    'icon' => '🧩',
    'category' => 'Marketing',
    // Optional: override owner if needed
    // 'owner' => 'page-builder',
    // Optional: module|plugin
    // 'owner_type' => 'module',
  ],
];
```

### Plugin definition file

Create file at `plugins/{plugin-slug}/config/webstudio-components.php`.

```php
<?php

return [
  [
    'key' => 'cta-strip',
    'label' => 'CTA Strip',
    'description' => 'Call-to-action strip with headline and button.',
    'icon' => '📣',
    'category' => 'Marketing',
  ],
];
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
- `props_schema` (optional): custom props metadata

### Normalization rules

- If `owner` is missing, owner defaults to module/plugin slug.
- If `key` has no namespace, key is prefixed as `{owner}:{key}`.
- If `html_template` is missing, a default section template is generated.

### End-to-end verification checklist

1. Add or update definition file in module/plugin.
2. Ensure module/plugin is enabled.
3. Open `GET /api/v1/{admin_prefix}/page-builder/registry` and verify `owner`, `owner_type`, `source`, and normalized `key`.
4. Open `GET /api/v1/{admin_prefix}/page-builder/data/{projectId}` and verify `registeredComponents` payload.
5. Open editor and confirm Components tab shows a group named by owner with registered items.
