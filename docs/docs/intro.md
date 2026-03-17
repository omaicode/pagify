---
sidebar_position: 1
slug: /
---

# Pagify Documentation

Welcome to the technical documentation for Pagify CMS.

## Audience

- Backend developers maintaining the Laravel modular platform
- Frontend developers working on admin and theme systems
- Operators handling environment setup and production runbook tasks

## Core Sections

- Operations: environment setup, daily commands, troubleshooting
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
