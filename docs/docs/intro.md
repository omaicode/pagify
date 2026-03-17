---
sidebar_position: 1
slug: /
---

# Introduction

Welcome to the technical documentation for Pagify CMS.

This documentation is designed to be easy to navigate for both new contributors and experienced maintainers.

## Audience

- Backend developers maintaining the Laravel modular platform
- Frontend developers working on admin and theme systems
- Operators handling environment setup and production runbook tasks

## Start Here

If you are new to the project:

1. Read [Quickstart](./getting-started/quickstart.md)
2. Follow [Learning Path](./getting-started/learning-path.md)
3. Use [Runbook](./operations/runbook.md) as your daily command reference

## Documentation Structure

### Getting Started

- Quick onboarding with environment setup and first successful run
- Suggested learning sequence for backend, admin UI, and themes

### Overview

- Platform capability map based on currently implemented features
- System architecture and cross-cutting design principles

### Module Guides

- Core, Content, Media, Page Builder, and Updater deep-dive pages

### User Guides

- Practical guides for content teams and platform operators

### Advanced Guides

- How to extend the Admin UI safely and consistently
- How to customize frontend themes with a production-ready workflow

### Operations

- Installation and deployment checklist
- Daily runbook and troubleshooting

### Reference

- Module-organized API reference for classes and public methods (standalone API Reference section)
- API surface summary by module
- Pagify-specific Artisan command reference
- Admin UI: component conventions and frontend migration rules
- Themes: manifest contract and runtime behavior for theme delivery

## Getting Started

Install docs dependencies:

```bash
cd docs
npm install
```

Start local docs site:

```bash
cd docs
npm run start
```

Production build:

```bash
cd docs
npm run build
```

## Contribution Workflow

- Keep docs concise and operational.
- Prefer concrete file paths and commands.
- Verify internal links before merging.
- Update related runbook/theme/admin docs in the same pull request when behavior changes.

See [Runbook](./operations/runbook.md) for environment and troubleshooting details.
