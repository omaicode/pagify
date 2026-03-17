---
sidebar_position: 4
title: Page Builder Module
---

# Page Builder Module

The Page Builder module provides admin tooling for composing and publishing pages.

## Responsibilities

- Page CRUD in admin interface
- Page preview and publish actions
- Section and template library persistence
- Registry endpoints for builder capabilities

## Key admin routes

- `/{admin_prefix}/page-builder/pages`
- `/{admin_prefix}/page-builder/pages/create`
- `/{admin_prefix}/page-builder/pages/{page}/edit`
- `/{admin_prefix}/page-builder/pages/{page}/preview`

## Key API groups

- `api/v1/{admin_prefix}/page-builder/registry`
- `api/v1/{admin_prefix}/page-builder/editor/access-token`
- `api/v1/{admin_prefix}/page-builder/editor/verify-token`
- `api/v1/{admin_prefix}/page-builder/editor/contract`

## Iframe editor integration

Admin UI embeds the editor in an iframe and exchanges state via `postMessage`.

### Token endpoints

- `POST api/v1/{admin_prefix}/page-builder/editor/access-token`
	- Requires authenticated admin session.
	- Used by admin UI to mint short-lived editor token.
	- Optional payload: `page_id`, `theme`.
- `POST api/v1/{admin_prefix}/page-builder/editor/verify-token`
	- No admin session required.
	- Used by external editor host to validate signature/claims/expiry.
	- Payload: `token`.
- `GET api/v1/{admin_prefix}/page-builder/editor/contract`
	- No admin session required.
	- Used by external editor host to fetch runtime protocol contract and token endpoint URLs.

### Message contract (namespace: `pagify:editor`)

Source of truth in admin codebase:

- `themes/admin/default/resources/js/PageBuilder/iframeMessageContract.js`
- `PAGE_BUILDER_IFRAME_PROTOCOL_VERSION = 1`
- Runtime JSON: `GET api/v1/{admin_prefix}/page-builder/editor/contract`

- Parent -> iframe
	- `pagify:editor:init`
	- `pagify:editor:set-layout`
	- `pagify:editor:flush`
	- `pagify:editor:search`
	- `pagify:editor:token-refresh-result`
- Iframe -> parent
	- `pagify:editor:ready`
	- `pagify:editor:layout-change`
	- `pagify:editor:error`
	- `pagify:editor:token-refresh-request`

### Security notes

- Restrict iframe origins with `PAGE_BUILDER_IFRAME_EDITOR_ORIGIN`.
- Keep `PAGE_BUILDER_IFRAME_EDITOR_TOKEN_TTL` short.
- Rotate `PAGE_BUILDER_IFRAME_EDITOR_TOKEN_SECRET` periodically.
- Reject tokens failing `iss`, `aud`, signature, or `exp` validation.

## Lifecycle

1. Create page draft.
2. Compose content in Webstudio editor.
3. Preview in admin.
4. Publish page.

## Testing coverage highlights

- page builder lifecycle
- page builder theme render integration
