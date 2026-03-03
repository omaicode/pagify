# Core module roadmap

Last updated: 2026-03-04

## Scope

This roadmap tracks delivery status for the Core module backbone and defines the next execution plan for Phase P3.

---

## Progress summary

- Phase P1: completed
- Phase P2: completed
- Phase P3: planned

---

## P1 done checklist

### Security and authorization
- [x] Policy-based authorization for API token page and API endpoints
- [x] Replaced ad-hoc guards with Laravel `authorize()` flow in controllers
- [x] Sidebar and dashboard token action visibility follow effective permissions

### Audit baseline
- [x] Added automatic audit middleware for mutation routes (`POST`, `PUT`, `PATCH`, `DELETE`)
- [x] Added explicit login/logout audit entries
- [x] Audit logger is fail-safe and does not block requests on persistence errors

### Verification
- [x] Unit + feature suite green (environment-dependent permission tests are skipped when sqlite driver is unavailable)
- [x] Frontend build is green

---

## P2 done checklist

### Event bus and hooks contract
- [x] Added hook subscriber contract (`CoreHookSubscriber`)
- [x] EventBus supports subscriber registration through contract
- [x] Core provider loads and registers subscribers from config (`core.hook_subscribers`)
- [x] Unit tests cover hook dispatch, event-to-hook bridge, and subscriber registration

### i18n and locale UX
- [x] Added locale update endpoint for authenticated admin (`POST /admin/locale`)
- [x] Locale resolution prefers admin locale, then site locale, then default locale
- [x] Inertia shared props expose `locale`, `supportedLocales`, `localeUpdateUrl`, and UI translations
- [x] Admin layout supports locale switch UX
- [x] Added UI translation dictionaries (`en`, `vi`)
- [x] Applied translations to Search, Audit, and API Tokens pages/components

### Documentation
- [x] Added integration notes for hook subscribers and admin i18n flow in `README.md`

---

## P3 execution plan

### P3.1 Module runtime management
- [ ] Move module enable/disable state from config-only to runtime persistence (DB-backed)
- [ ] Add cache invalidation strategy and admin controls for module state
- [ ] Add health checks for module registry consistency (config vs DB)

### P3.2 API layer hardening
- [ ] Introduce FormRequest classes for admin token and locale endpoints
- [ ] Introduce API Resource / response DTO patterns for consistency
- [ ] Standardize API error envelope and codes across all core endpoints

### P3.3 Audit maturation
- [ ] Expand audit coverage to model-level changes (create/update/delete events)
- [ ] Add metadata redaction policy for sensitive keys
- [ ] Add retention/cleanup strategy for audit table growth

### P3.4 Multi-site completeness
- [ ] Add stronger site resolution strategy (domain/header fallback policy)
- [ ] Add tests for cross-site isolation and scope enforcement
- [ ] Validate all core entities consistently apply site ownership rules

### P3.5 Test infrastructure improvements
- [ ] Enable permission-denied integration tests without skips (sqlite driver or dedicated DB profile)
- [ ] Add end-to-end tests for locale switch persistence and token lifecycle
- [ ] Add regression tests for hook subscriber loading from config

---

## Acceptance criteria for P3 completion

- All P3 checklist items implemented or explicitly deferred with rationale.
- No skipped security-critical tests in CI.
- Core admin flows (auth, audit, locale, token, module state) pass feature tests.
- Build and test pipelines are green.

---

## Suggested implementation order

1. P3.5 test infrastructure
2. P3.1 module runtime management
3. P3.2 API hardening
4. P3.3 audit maturation
5. P3.4 multi-site completeness

This order reduces risk by stabilizing the test base first, then implementing runtime and API contracts before broader behavior changes.
