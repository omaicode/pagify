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

## `GET /data/{projectId}` behavior

The compatibility data endpoint is the source of truth for bootstrapping Webstudio for a specific page.

- Returns `id`, `version`, and `projectId` for the selected page state.
- Returns persisted design state such as `instances`, `props`, `styles`, `styleSources`, `styleSourceSelections`, `resources`, and `dataSources`.
- Returns page tree metadata from the current database state so page title, slug, and publish state stay fresh when switching pages.
- Returns media assets mapped from the Pagify media system.

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
5. Webstudio saves page-scoped UI state through `/patch`.
6. User toggles draft or publish state through page CRUD APIs.

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
