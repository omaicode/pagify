# Content module roadmap

Last updated: 2026-03-04

## Scope

Roadmap này định nghĩa lộ trình triển khai module `content` cho Pagify theo hướng JSON-first metadata, multi-site safe, policy-first authorization, và tương thích đầy đủ với core foundation hiện tại (auth, audit, event bus, i18n, admin shell, API envelope).

---

## Guiding decisions (locked)

- Data strategy: JSON-first metadata cho content schema + entry values.
- Media field (MVP): lưu URL/path string (không build media subsystem riêng ở phase đầu).
- Permissions: per-content-type (không chỉ per-module).
- Versioning MVP: snapshot + rollback + key-level diff.
- Visual schema builder: save schema và queue migration plan; không chạy DDL trực tiếp trong request.

---

## Progress summary

- Phase C0: completed
- Phase C1: completed
- Phase C2: completed
- Phase C3: completed
- Phase C4: completed
- Phase C5: completed
- Phase C6: completed
- Phase C7: planned
- Phase C8: planned

---

## Phase C0 — Module bootstrap & architecture baseline

- [x] Create module scaffold (`modules/content`) with provider, config, routes, resources, tests.
- [x] Register module package/composer wiring and ensure autoload discovery.
- [x] Add Content menu entry into admin shell via module registry.
- [x] Wire middleware stack parity with core (site resolve, locale, auth, audit, API envelope).
- [x] Add base permissions namespace for content (`content.*`).

Deliverables:
- `modules/content/src/Providers/ContentServiceProvider.php`
- `modules/content/config/content.php`
- `modules/content/routes/content-routes.php`

---

## Phase C1 — Content Type Modeler (DB + Admin CRUD)

- [x] Add migrations for `content_types` and `content_type_fields`.
- [x] Implement models (`ContentType`, `ContentField`) with site ownership scope.
- [x] Build admin CRUD for content types (list/create/edit/delete).
- [x] Support field definitions: Text, RichText, Number, Date, Boolean, Select, Media, Relation, Repeater, Conditional.
- [x] Validate schema payload using FormRequest + service-level schema guard.
- [x] Add policy checks for type management (per-content-type aware).

Deliverables:
- `modules/content/src/Models/ContentType.php`
- `modules/content/src/Models/ContentField.php`
- `modules/content/src/Http/Controllers/Admin/ContentTypeController.php`
- `modules/content/src/Services/ContentTypeService.php`

---

## Phase C2 — Content Entries engine (dynamic form from schema)

- [x] Add migration/model for `content_entries`.
- [x] Implement dynamic form resolver from content type schema.
- [x] Implement admin CRUD entries for each content type slug.
- [x] Validate entry payload against field schema rules.
- [x] Enforce multi-site isolation in all read/write entry operations.
- [x] Add audit logging for entry mutations.

Deliverables:
- `modules/content/src/Models/ContentEntry.php`
- `modules/content/src/Services/EntrySchemaResolver.php`
- `modules/content/src/Services/ContentEntryService.php`
- `modules/content/resources/js/Pages/Admin/Entries/Form.vue`

---

## Phase C3 — Versioning (revision history, diff, rollback)

- [x] Add migration/model for `content_entry_revisions`.
- [x] Auto-create revision snapshot on create/update/publish/rollback.
- [x] Implement key-level diff calculation for revision compare view.
- [x] Implement rollback endpoint/action creating a new revision head.
- [x] Add UI history timeline + diff + rollback actions.
- [x] Ensure revision operations are permission-protected and audited.

Deliverables:
- `modules/content/src/Models/ContentEntryRevision.php`
- `modules/content/src/Services/EntryRevisionService.php`
- `modules/content/src/Services/EntryDiffService.php`
- `modules/content/resources/js/Pages/Admin/Entries/Revisions.vue`

---

## Phase C4 — Draft / Publish / Schedule workflow

- [x] Add entry state flow: `draft`, `published`, `scheduled`.
- [x] Add publish/unpublish timestamps and scheduler metadata.
- [x] Implement schedule publish/unpublish queue processing command/job.
- [x] Emit hooks/events for publish transitions.
- [x] Add permission gates for publish actions.
- [x] Add tests for immediate publish and delayed schedule processing.

Deliverables:
- `modules/content/src/Jobs/ProcessScheduledPublicationJob.php`
- `modules/content/src/Console/Commands/ProcessScheduledContentCommand.php`
- `modules/content/src/Services/PublishingWorkflowService.php`

---

## Phase C5 — Relation engine

- [x] Add migration/model for `content_relations`.
- [x] Implement relation types: hasOne, hasMany, manyToMany between entries.
- [x] Build relation picker API for admin form UI.
- [x] Resolve relation hydration for entry detail/list payloads.
- [x] Enforce cross-site relation constraints.
- [x] Add cycle/invalid-target protection rules.

Deliverables:
- `modules/content/src/Models/ContentRelation.php`
- `modules/content/src/Services/RelationResolver.php`
- `modules/content/src/Http/Controllers/Api/AdminRelationPickerController.php`

---

## Phase C6 — Content API auto-generation

- [x] Build REST endpoints by content type slug (`list`, `get`).
- [x] Add filtering, sorting, pagination contract.
- [x] Add API resources for consistent payload shape.
- [x] Enforce per-content-type permissions for API access.
- [x] Keep envelope compatibility with core API contract.
- [x] Add feature tests for query/filter/pagination/security.

Deliverables:
- `modules/content/src/Http/Controllers/Api/ContentApiController.php`
- `modules/content/src/Http/Resources/*`
- `modules/content/src/Http/Requests/Api/*`

---

## Phase C7 — Visual Schema Builder (UI drag-drop + queue trigger)

- [ ] Build drag-drop schema editor UI for content type fields.
- [ ] Support conditional field blocks and repeater nesting metadata.
- [ ] Save builder output to schema JSON.
- [ ] Create migration plan job on schema save (queue trigger).
- [ ] Add admin status view for queued migration plans.
- [ ] Prevent direct DDL execution from web request thread.

Deliverables:
- `modules/content/resources/js/Pages/Admin/Types/Builder.vue`
- `modules/content/src/Jobs/QueueSchemaMigrationPlanJob.php`
- `modules/content/src/Services/SchemaMigrationPlanner.php`

---

## Phase C8 — Test infrastructure & hardening for content

- [ ] Add feature tests for full authoring lifecycle (type -> entry -> revision -> publish -> API).
- [ ] Add multi-site isolation tests across all content entities.
- [ ] Add regression tests for hook/event dispatch integration.
- [ ] Add permission-denied tests (no skips) for all content admin/API routes.
- [ ] Add seed helpers/factories for content types, fields, entries, revisions.
- [ ] Run and stabilize full test suite.

Deliverables:
- `tests/Feature/Content/*`
- `tests/Unit/Content/*`
- `modules/content/database/factories/*`

---

## Acceptance criteria (module content MVP)

- All phases C0 -> C8 are complete, or deferred items are explicitly documented with rationale.
- Core + Content test suites pass without security-critical skipped tests.
- Admin can model content types and manage entries without writing YAML/code.
- Revisions, rollback, publish workflow, and API access control are functional.
- Multi-site boundaries are enforced consistently for type, entry, relation, and revision data.

---

## Suggested implementation order

1. C0 bootstrap
2. C1 type modeler
3. C2 entries dynamic form
4. C3 revisions
5. C4 publish workflow
6. C5 relation engine
7. C6 content API
8. C7 visual builder + queue trigger
9. C8 hardening + full regression
