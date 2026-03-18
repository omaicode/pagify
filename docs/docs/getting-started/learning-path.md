---
sidebar_position: 2
title: Learning Path
---

# Learning Path

Use this roadmap to learn Pagify in a practical order.

Do not read the docs in sidebar order. Read them in task order.

## Read These First

Everyone new to the project should start with the same three pages:

1. [Quickstart](./quickstart.md)
2. [How Pagify Works](../architecture/how-pagify-works.md)
3. [System Overview](../architecture/system-overview.md)

After that, pick the track that matches the work you need to do.

## Track 1: Platform Foundations (Recommended First)

1. Read [Quickstart](./quickstart.md)
2. Read [How Pagify Works](../architecture/how-pagify-works.md)
3. Read [System Overview](../architecture/system-overview.md)
4. Read [Runbook](../operations/runbook.md)
5. Learn module boundaries and project structure in the codebase

Goal: you can run the project, debug basic issues, and execute tests confidently.

## Track 2: Admin Experience

1. Read [Core Module](../modules/core-module.md)
2. Read [Admin UI Primitives](../admin/admin-ui-primitives.md)
3. Read [Admin UI Extension](../guides/advanced/admin-ui-extension.md)
4. Read [Page Builder Module](../modules/page-builder-module.md)
5. Implement a small admin feature using canonical UI props

Goal: you can ship admin pages with consistent UX and maintainable components.

## Track 3: Frontend Theme Delivery

1. Read [System Overview](../architecture/system-overview.md)
2. Read [Theme Development](../guides/advanced/theme-development.md)
3. Read [Theme Development Contract](../reference/theme-development-contract.md)
4. Read [Theme Customization](../guides/advanced/theme-customization.md)
5. Build or customize a theme with safe fallback behavior

Goal: you can deliver production-safe theme changes and troubleshoot rendering issues.

## Track 4: Platform Extensibility (Plugin)

1. Read [Core Module](../modules/core-module.md)
2. Read [Plugin Development](../guides/advanced/plugin-development.md)
3. Review plugin-related commands in [Artisan Commands](../reference/artisan-commands.md)
4. Create a sample plugin with `cms:make-plugin` and validate lifecycle in Admin > Modules

Goal: you can scaffold, integrate, and operate plugins with predictable behavior.

## Track 5: Operator And Deployment

1. Read [Quickstart](./quickstart.md)
2. Read [Installation and Deployment](../operations/installation-and-deployment.md)
3. Read [Runbook](../operations/runbook.md)
4. Practice cache clear, queue worker, and frontend rebuild workflows

Goal: you can deploy, recover, and verify a Pagify environment safely.

## Suggested 7-Day Plan

- Day 1: Setup + first successful run
- Day 2: How Pagify Works + System Overview
- Day 3: Runbook commands and troubleshooting drills
- Day 4: One module guide relevant to your area
- Day 5: Admin UI or theme track deep dive
- Day 6: Implement one safe, small change
- Day 7: End-to-end verification and documentation updates

## Definition of Done for New Contributors

You are ready to contribute when you can:

- bootstrap local environment without support
- explain which module owns the feature you are changing
- run tests and identify failing scope quickly
- add one small admin or theme change with correct conventions
- update related docs in the same pull request
