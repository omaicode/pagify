---
sidebar_position: 2
title: Admin Operations Guide
---

# Admin Operations Guide

This guide helps operators and admins manage day-to-day Pagify operations.

## Daily routine

1. Verify app and queue health.
2. Review audit logs for unexpected mutations.
3. Check plugin and module health states.
4. Confirm theme activation per site.

## Admin areas to monitor

- Dashboard
- Audit Logs
- Modules and Plugins
- Themes
- API Tokens
- Permissions and Admin Groups

## Incident response basics

### Admin login issues

- verify auth reset flow is working
- reset or reseed admin account when needed

### Queue backlog or delayed publish

- ensure queue worker is running
- inspect failed jobs and retry safely

### Theme rendering issues

- validate active theme manifest
- clear app and theme caches
- verify fallback theme is healthy

### Plugin regression

- disable problematic plugin in admin
- inspect extension conflicts and logs

## Security hygiene

- use least-privilege roles
- rotate API tokens periodically
- keep audit retention policy active
- review plugin sources before installation

For command-level details, see [Runbook](../operations/runbook.md).
