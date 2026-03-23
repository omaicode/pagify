---
sidebar_position: 1
title: Core Module
---

# Core Module

The Core module provides platform-level capabilities shared by all other modules.

## Responsibilities

- Admin authentication and session lifecycle
- Password reset via email link flow
- Admin dashboard shell and settings pages
- Role/group/permission management
- Admin account management and profile updates
- API token management
- Module/plugin/theme management endpoints
- Audit logs and access governance
- Frontend fallback routing and theme asset serving

## Key admin routes

- `/{admin_prefix}/login`
- `/{admin_prefix}/forgot-password`
- `/{admin_prefix}/reset-password/{token}`
- `/{admin_prefix}/dashboard`
- `/{admin_prefix}/permissions`
- `/{admin_prefix}/admin-groups`
- `/{admin_prefix}/admins`
- `/{admin_prefix}/plugins`
- `/{admin_prefix}/themes`

## Key API groups

- `api/v1/{admin_prefix}/tokens`
- `api/v1/{admin_prefix}/permissions`
- `api/v1/{admin_prefix}/admin-groups`
- `api/v1/{admin_prefix}/admins`
- `api/v1/{admin_prefix}/plugins`
- `api/v1/{admin_prefix}/themes`

## Operational commands

- `core:audit:cleanup`
- `cms:make-plugin`
- `cms:make-theme`

## Testing coverage highlights

- access management
- admin end-to-end flow
- plugin manager behavior
- theme management behavior
- profile flow
- frontend fallback and theme asset hardening
