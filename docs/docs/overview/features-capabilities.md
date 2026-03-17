---
sidebar_position: 1
title: Features and Capabilities
---

# Features and Capabilities

Pagify is a modular CMS platform focused on multi-site operations, page delivery, and extensibility.

## Platform capabilities

- Modular architecture with independently deployable domains
- Multi-site awareness across admin and public rendering
- Admin authentication with password reset flow
- Role and permission management
- API token management for admin APIs
- Audit logging for traceability
- Plugin lifecycle management (install, enable/disable, uninstall)
- Theme lifecycle management with per-site activation

## Media capabilities

- Media library with folder organization
- Direct upload sessions with chunk/complete flow
- Asset preview and download endpoints
- Asset metadata update and delete operations

## Page Builder capabilities

- Visual page CRUD flow for admin users
- Revisions and rollback for pages
- Section/template library management
- Registry and template APIs for builder integrations

## Updater capabilities

- Dry-run updates
- Module-level and all-module updates
- Execution history and details
- Rollback support for update executions

## Frontend runtime capabilities

- Fallback routing for unresolved frontend paths
- Theme asset exposure with path validation
- Twig sandbox for safer community theme rendering
- Theme fallback chain for resilient rendering

## Quality and reliability coverage

The active feature test suite covers:

- installer bootstrap and gating
- core access and admin E2E paths
- page builder lifecycle and theme render
- updater API and output sanitization
- frontend fallback and twig sandbox behavior
