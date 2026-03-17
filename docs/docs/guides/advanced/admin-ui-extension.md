---
sidebar_position: 1
title: Admin UI Extension
---

# Admin UI Extension

This guide explains how to extend the Admin UI in a way that stays consistent with Pagify conventions.

## When to use this guide

Use this guide when you are:

- adding a new admin page
- introducing a new form flow
- extending existing admin modules

## Core principles

- Keep business logic out of Vue pages.
- Use canonical UI primitive props (`tag`, `tone`, `radius`, `fullWidth`).
- Cover states explicitly: loading, empty, error, success, and forbidden.
- Do not hardcode user-facing text. Always use translation files.

## Recommended implementation flow

1. Define route and permission checks.
2. Define backend payload contract (request validation and response shape).
3. Build UI with primitives from the active admin theme.
4. Add i18n strings for `en` and `vi`.
5. Add feature tests for success and permission-denied scenarios.

## Minimal page template strategy

- Header: `UiPageHeader`
- Content shell: `UiCard`
- Form structure: `UiField`, `UiInput`, `UiSwitch`
- Actions: `UiButton`, `UiCrudActions`
- Feedback: `UiAlert`, toast notifications

## UX consistency checklist

- Destructive actions require explicit confirmation.
- Success/failure feedback appears immediately after mutation.
- Save/submit buttons are disabled during pending state.
- Error messages are actionable and specific.

## i18n checklist

Before merging, confirm:

- all new UI labels exist in English and Vietnamese translation files
- no hardcoded text remains in Vue pages
- translation keys are grouped by feature area

## Regression checklist

- page loads with valid permission
- page returns forbidden state without permission
- mutation success updates UI state
- mutation validation errors map to form fields

For daily commands and troubleshooting, see [Runbook](../../operations/runbook.md).
