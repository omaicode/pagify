---
sidebar_position: 2
title: Content Module
---

# Content Module

The Content module is responsible for structured content modeling, authoring workflow, and API delivery.

## Responsibilities

- Content type CRUD
- Schema builder editing and plan execution status
- Entry CRUD for each content type
- Publish/unpublish/schedule transitions
- Revisions and rollback
- Relation picker support for linked content
- Public content delivery APIs

## Key admin routes

- `/{admin_prefix}/content/types`
- `/{admin_prefix}/content/types/{contentType}/builder`
- `/{admin_prefix}/content/{contentTypeSlug}/entries`
- `/{admin_prefix}/content/{contentTypeSlug}/entries/{entryId}/revisions`

## Key API groups

- Public: `api/v1/content/{contentTypeSlug}`
- Public detail: `api/v1/content/{contentTypeSlug}/{entrySlug}`
- Admin helper: `api/v1/{admin_prefix}/content/{contentTypeSlug}/relations/picker`

## Workflow model

1. Define content type and schema.
2. Create entries as draft.
3. Publish immediately or schedule transitions.
4. Track revisions and rollback when needed.

## Testing coverage highlights

- content type CRUD
- schema builder lifecycle
- entry CRUD and revision rollback
- publishing workflow and scheduling
- permission denied matrix
- multi-site isolation hardening
- hook event integration
- relation engine behavior
