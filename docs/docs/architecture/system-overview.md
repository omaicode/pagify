---
sidebar_position: 2
title: System Overview
---

# System Overview

This page describes how Pagify is structured and how feature domains interact.

## High-level architecture

Pagify is organized into Laravel modules:

- Core: auth, access control, plugin/theme manager, admin shell
- Content: content model, schema builder, entries, revisions, delivery
- Media: media asset ingestion and retrieval
- Page Builder: page composition and revision workflow
- Updater: package/module update orchestration

## Request flow

1. Incoming request enters Laravel web/api middleware stack.
2. Site context and locale are resolved.
3. Request is routed to module controllers.
4. Authorization and policy checks are enforced.
5. Domain services execute business logic.
6. Response is returned as Inertia page or JSON API payload.

## Cross-cutting concerns

- Multi-site context resolution
- i18n and locale handling
- Audit log recording
- Queue-based async processing for long-running tasks
- Validation via form requests and structured contracts

## Data and state model

- Content and page entities maintain revision history.
- Publish state supports draft/live/scheduled transitions.
- Theme activation is scoped per site with fallback logic.
- Module/plugin state is centrally managed for safe operations.

## Security model

- Admin routes require authenticated web guard access.
- Management APIs are role/permission gated.
- Twig rendering for frontend themes runs in sandbox mode.
- Theme asset exposure enforces safe path and directory rules.

## Operational model

- Daily development uses composer-level workflow commands.
- Queue workers are required for asynchronous operations.
- Cache clear and theme cache clear commands support recovery and deployment hygiene.

## Extensibility model

- Plugins can register hooks and feature extensions.
- Theme contract enforces manifest and runtime compatibility requirements.
- Admin UI primitives and conventions keep interface behavior consistent across modules.
