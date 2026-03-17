---
sidebar_position: 2
title: Learning Path
---

# Learning Path

Use this roadmap to learn Pagify in a practical order.

## Track 1: Platform Foundations (Recommended First)

1. Read [Quickstart](./quickstart.md)
2. Read [Runbook](../operations/runbook.md)
3. Learn module boundaries and project structure in the codebase

Goal: you can run the project, debug basic issues, and execute tests confidently.

## Track 2: Admin Experience

1. Read [Admin UI Primitives](../admin/admin-ui-primitives.md)
2. Read [Admin UI Extension](../guides/advanced/admin-ui-extension.md)
3. Implement a small admin feature using canonical UI props

Goal: you can ship admin pages with consistent UX and maintainable components.

## Track 3: Frontend Theme Delivery

1. Read [Theme Development](../guides/advanced/theme-development.md)
2. Read [Theme Development Contract](../reference/theme-development-contract.md)
3. Read [Theme Customization](../guides/advanced/theme-customization.md)
4. Build or customize a theme with safe fallback behavior

Goal: you can deliver production-safe theme changes and troubleshoot rendering issues.

## Track 4: Platform Extensibility (Plugin)

1. Read [Plugin Development](../guides/advanced/plugin-development.md)
2. Review plugin-related commands in [Artisan Commands](../reference/artisan-commands.md)
3. Create a sample plugin with `cms:make-plugin` and validate lifecycle in Admin > Modules

Goal: you can scaffold, integrate, and operate plugins with predictable behavior.

## Suggested 7-Day Plan

- Day 1: Setup + first successful run
- Day 2: Runbook commands and troubleshooting drills
- Day 3: Admin UI primitives and conventions
- Day 4: Implement one admin page improvement
- Day 5: Theme development baseline + contract validation
- Day 6: Create one theme or plugin customization safely
- Day 7: End-to-end verification and documentation updates

## Definition of Done for New Contributors

You are ready to contribute when you can:

- bootstrap local environment without support
- run tests and identify failing scope quickly
- add one small admin or theme change with correct conventions
- update related docs in the same pull request
