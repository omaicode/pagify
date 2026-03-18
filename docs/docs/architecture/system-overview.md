---
sidebar_position: 2
title: System Overview
---

# System Overview

This page describes how Pagify is structured, how feature domains interact, and where a new contributor should look first when debugging.

## High-level architecture

Pagify is organized into Laravel modules with clear ownership boundaries:

- Core: auth, access control, plugin/theme manager, admin shell
- Media: media asset ingestion and retrieval
- Page Builder: page composition, editor integration, and revision workflow
- Updater: package/module update orchestration

The most important rule for understanding the codebase is this:

- find the owning module first
- then find the controller
- then find the service
- then find the persistence model

## How To Read The Architecture

Think of Pagify as three layers working together:

### Platform layer

Shared concerns used by every module.

- auth
- permissions
- middleware
- site resolution
- locale resolution
- audit logging

### Domain layer

Business features owned by modules.

- pages
- media assets
- themes
- updates

### Delivery layer

How behavior reaches users or other systems.

- admin UI pages
- JSON APIs
- Blade or theme-rendered frontend responses
- queue workers and background execution

## Request flow

1. Incoming request enters Laravel web/api middleware stack.
2. Site context and locale are resolved.
3. Request is routed to module controllers.
4. Authorization and policy checks are enforced.
5. Domain services execute business logic.
6. Response is returned as Inertia page or JSON API payload.

For frontend page delivery, the last step may instead be theme rendering.

## Cross-cutting concerns

- Multi-site context resolution
- i18n and locale handling
- Audit log recording
- Queue-based async processing for long-running tasks
- Validation via form requests and structured contracts

## Data and state model

Some state is persistent and some is only runtime. New contributors should separate these early.

### Persistent state

- page entities and revision history
- publish state for managed pages
- media assets and metadata
- plugin, module, and theme state
- page-builder saved UI state

### Runtime state

- current request site context
- active admin session
- in-memory editor state before persistence
- queue execution context

Theme activation is scoped per site with fallback logic, and module or plugin state is centrally managed for safe operations.

## Security model

- Admin routes require authenticated web guard access.
- Management APIs are role/permission gated.
- Twig rendering for frontend themes runs in sandbox mode.
- Theme asset exposure enforces safe path and directory rules.

## Operational model

- Daily development uses composer-level workflow commands.
- Queue workers are required for asynchronous operations.
- Cache clear and theme cache clear commands support recovery and deployment hygiene.
- Frontend asset rebuilds are part of normal development for admin and theme changes.

## Extensibility model

- Plugins can register hooks and feature extensions.
- Theme contract enforces manifest and runtime compatibility requirements.
- Admin UI primitives and conventions keep interface behavior consistent across modules.

## Where To Start When Something Breaks

- login, permissions, admin shell, modules, themes: Core
- upload, broken asset URL, media metadata: Media
- page edit, publish, editor runtime, page-scoped state: Page Builder
- update execution, rollback, update history: Updater

## Read Next

- [Core Module](../modules/core-module.md)
- [Page Builder Module](../modules/page-builder-module.md)
- [Runbook](../operations/runbook.md)
