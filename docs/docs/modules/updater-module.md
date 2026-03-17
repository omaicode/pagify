---
sidebar_position: 5
title: Updater Module
---

# Updater Module

The Updater module manages controlled module/package update workflows from admin APIs.

## Responsibilities

- Execution history listing and details
- Dry-run updates
- Update single module or all mapped modules
- Rollback update executions
- Sanitized update output for safer UI display

## Key admin route

- `/{admin_prefix}/updater`

## Key API groups

- `api/v1/{admin_prefix}/updater/executions`
- `api/v1/{admin_prefix}/updater/executions/dry-run`
- `api/v1/{admin_prefix}/updater/executions/module/{module}`
- `api/v1/{admin_prefix}/updater/executions/all`
- `api/v1/{admin_prefix}/updater/executions/{execution}/rollback`

## Operational commands

- `updater:module`
- `updater:all`
- `updater:rollback`

## Testing coverage highlights

- updater API behavior
- updater page rendering
- updater output sanitization
