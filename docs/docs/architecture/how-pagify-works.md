---
sidebar_position: 1
title: How Pagify Works
---

# How Pagify Works

This page gives a newcomer-friendly mental model of the platform before you dive into individual modules.

## In One Paragraph

Pagify is a Laravel-based CMS organized into modules. Requests enter through Laravel middleware, site and locale context are resolved, the request is routed into the owning module, domain services perform the business logic, and the response is returned as either an admin UI page, a JSON API payload, or a theme-rendered frontend page.

## The Main Building Blocks

### Core

Core owns the platform shell.

- admin authentication and sessions
- permissions and admin groups
- modules, plugins, and themes
- audit logs
- shared middleware and platform conventions

### Media

Media owns uploaded files and asset metadata.

- uploads
- folders
- asset retrieval
- asset lifecycle management

### Page Builder

Page Builder owns page management and editor integration.

- page CRUD
- page publish state
- visual editor integration
- persisted page-builder state

### Updater

Updater owns controlled update execution.

- dry runs
- execution history
- rollback support

## What Happens On A Typical Admin Request

1. The request enters Laravel.
2. Middleware resolves site and locale context.
3. Auth and permission checks run.
4. The request is routed to the owning module controller.
5. Form requests, policies, and services execute the business logic.
6. The response is returned as Inertia, Blade, or JSON.

## What Happens On A Typical Frontend Request

1. The public request enters Laravel.
2. Site context is resolved from host and routing rules.
3. The platform finds the matching page or frontend fallback.
4. Theme rendering is selected.
5. Theme assets and Twig sandbox rules are applied.
6. The rendered frontend response is returned.

## Where State Lives

One of the easiest ways to get lost in Pagify is to confuse runtime state with persisted state.

### Persistent state

This is saved in the database or storage and survives restarts.

- admins, groups, permissions
- pages and publish status
- page builder saved UI state
- media assets and metadata
- module and theme settings

### Runtime state

This exists only while a request or client session is active.

- authenticated admin session
- request-scoped site context
- builder UI state before it is patched to the backend
- queue worker execution context

## A Practical Reading Order For The Codebase

If you are new, do not start from random controllers. Read the codebase in this order:

1. middleware and route registration
2. the owning module controller
3. the service layer called by that controller
4. the model or persistence layer
5. any frontend client code tied to that flow

## Common Mental Models

### Module ownership matters

When debugging, first answer: which module owns this behavior?

- login or permissions issue: Core
- upload or broken asset URL: Media
- page editing or publish issue: Page Builder
- update execution issue: Updater

### Reference docs are not onboarding docs

Use reference pages when you already know what you are looking for. Use architecture and module pages when you still need the big picture.

### UI integration may span backend and frontend

For example, the page builder editor is not just a frontend bundle. It also depends on Laravel routes, auth scopes, compatibility APIs, media mapping, and page-scoped persistence.

## If You Need To Contribute Safely

Before changing code, make sure you can answer:

- which module owns the feature
- which route or command triggers it
- which service performs the real business logic
- where the source of truth is stored
- which doc page should be updated together with the change

## Read Next

- [Learning Path](../getting-started/learning-path.md)
- [System Overview](./system-overview.md)
- the module guide for the area you need to change
