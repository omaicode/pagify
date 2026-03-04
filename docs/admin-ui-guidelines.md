# Admin UI guidelines

Last updated: 2026-03-04

## Goal

This document standardizes how to build the admin interface so modules in Pagify can integrate consistently in terms of UI/UX, permissions, navigation, and runtime behavior.

## Architecture principles

- Core provides a shared **Admin shell** (topbar, sidebar, breadcrumb, flash messages, site/locale context).
- A module should only extend through the UI contract (Module UI contract), not by creating a separate shell.
- All module pages/actions/widgets must register metadata so core can render, authorize, and observe behavior.
- Do not hard-code colors/typography/sizing outside existing design tokens.

## Navigation conventions

- First-level sidebar items should be grouped by domain (Core, Content, Extensions).
- Every menu item must have an `order` value to keep positioning stable across deployments.
- Routes must follow this naming convention: `module.admin.<resource>.<action>`.
- Every menu item/action must have explicit permission guards.

## Route deprecation policy

- Canonical admin routes are the only routes that should be referenced by new code.
- Legacy aliases may be temporarily kept for compatibility and must redirect to canonical routes.
- Every alias must include:
  - deprecation start date
  - planned removal date
  - automated test coverage to ensure redirect behavior remains stable during the grace period
- Alias grace period target: maximum 90 days unless release constraints require extension.

### Current deprecated aliases (Content module)

- No active aliases.
- Legacy aliases were removed on 2026-03-04.

## Standard module page layout

Each admin page should follow this shared layout:

1. `Header`: title + subtitle + primary action.
2. `Context bar`: primary site/locale/status/filters.
3. `Content body`: table, cards, form, or builder.
4. `Secondary actions`: export/import/bulk actions.
5. `Feedback`: loading, empty, error, permission denied.

## Required UX states

Each page/action must support all of the following states:

- Loading (skeleton or lightweight spinner)
- Empty state (with CTA)
- Error state (clear message + suggested next step)
- Success feedback (flash/toast)
- Permission denied (403 view)

## Form and validation conventions

- Forms should use a consistent field label/help/error pattern.
- Validation should prioritize inline errors and include a summary at the top of the form.
- Long input/JSON fields must include examples and placeholders.
- Destructive actions (delete/publish rollback) must include a confirmation step.

## Site + locale awareness

- Every admin page must reflect the current site context.
- Do not display cross-site data unless explicitly required.
- Display text must support i18n with clear fallback behavior.

## Audit and telemetry

- Critical actions must write audit logs (`created`, `updated`, `deleted`, `published`, `rollback`).
- Include at least this metadata: module, entity_type, entity_id, action.
- Critical pages should include basic metrics (load time, error rate).

## Accessibility baseline

- Support keyboard navigation for main menus and form controls.
- Ensure clear focus state after page transitions or modal open.
- Ensure sufficient contrast and semantic headings (`h1`, `h2`, ...).

## Module UI contract

- All new modules must include a valid contract JSON file based on this schema:
  - `docs/module-ui-contract.schema.json`
- The module contract file should be located at:
  - `modules/<module>/ui-contract.json`
- The contract is used to:
  - render sidebar/menu
  - render action bars
  - render dashboard widgets
  - control visibility by permission/feature flag

## Adoption workflow for module teams

1. Create `ui-contract.json` based on the standard schema.
2. Validate JSON before opening a PR.
3. Register routes + permissions according to contract metadata.
4. QA must verify loading/empty/error/forbidden states.
5. Merge only when the contract is valid and route permission tests pass.

## Minimal example

```json
{
  "module": {
    "key": "content",
    "name": "Content",
    "version": "1.0.0"
  },
  "menus": [
    {
      "key": "content.dashboard",
      "label": "Content",
      "route": "content.admin.dashboard",
      "order": 30,
      "permission": "content.type.viewAny",
      "group": "Content"
    }
  ],
  "pages": [
    {
      "key": "content.types.index",
      "title": "Content types",
      "route": "content.admin.types.index",
      "permission": "content.type.viewAny",
      "layout": "admin.default"
    }
  ],
  "actions": [
    {
      "key": "content.types.create",
      "label": "Create content type",
      "route": "content.admin.types.create",
      "method": "GET",
      "permission": "content.type.create",
      "surface": "page-header"
    }
  ],
  "widgets": [
    {
      "key": "content.stats.total-types",
      "title": "Total content types",
      "type": "stat",
      "surface": "dashboard",
      "permission": "content.type.viewAny"
    }
  ]
}
```
