
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
