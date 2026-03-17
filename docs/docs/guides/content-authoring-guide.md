---
sidebar_position: 1
title: Content Authoring Guide
---

# Content Authoring Guide

This guide is for editors and developers who manage structured content in Pagify.

## Typical authoring flow

1. Create or select a content type.
2. Review the schema fields in builder.
3. Create a new entry draft.
4. Save and review validation feedback.
5. Publish now or schedule publication.
6. Roll back to a previous revision when needed.

## Best practices

- Keep schemas stable once in active usage.
- Use clear field labels and help text in schemas.
- Prefer scheduled publishing for time-sensitive releases.
- Verify relation fields with the relation picker before publishing.

## Review checklist before publishing

- title and slug are correct
- required fields are complete
- relations point to valid live entries
- media references are available
- SEO and metadata fields are reviewed

## Rollback strategy

If a live entry is incorrect:

1. Open revision history.
2. Compare desired revision.
3. Roll back and verify public API response.

## API verification quick check

Use public endpoints to verify final result:

- `GET /api/v1/content/{contentTypeSlug}`
- `GET /api/v1/content/{contentTypeSlug}/{entrySlug}`
