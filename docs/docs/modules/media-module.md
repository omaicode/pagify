---
sidebar_position: 3
title: Media Module
---

# Media Module

The Media module manages asset ingestion, organization, and retrieval for content and page workflows.

## Responsibilities

- Media library browsing
- Folder listing and creation
- Asset upload and metadata updates
- Asset preview and download
- Chunked upload sessions for large files

## Key admin routes

- `/{admin_prefix}/media`

## Key API groups

- `api/v1/{admin_prefix}/media/assets`
- `api/v1/{admin_prefix}/media/folders`
- `api/v1/{admin_prefix}/media/upload-sessions`

## Upload session flow

1. Create upload session.
2. Upload file chunks.
3. Complete upload session.
4. Asset becomes available in library.

## Testing coverage highlights

- media module bootstrap
- media usage indexing integration with content
