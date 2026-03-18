---
sidebar_position: 1
slug: /
---

# Introduction

Pagify is a modular Laravel CMS platform for multi-site administration, media management, page composition, and theme-driven frontend delivery.

This documentation is written for engineers and operators who need to do one of three things:

- run the platform locally
- understand how the main modules fit together
- make changes safely without guessing where behavior lives

## Start Here

If you are new to the project, read in this order:

1. [Quickstart](./getting-started/quickstart.md)
2. [How Pagify Works](./architecture/how-pagify-works.md)
3. [Learning Path](./getting-started/learning-path.md)
4. the module guide for the area you need to change

## What You Will Find Here

The docs are split by purpose, not just by topic.

### Getting Started

Use this section when you are new to the codebase.

- install and run the project
- learn the recommended reading order
- choose the right track for your role

### Architecture

Use this section when you need a mental model before reading code.

- how requests move through the platform
- how modules interact
- where data is persisted
- which runtime concerns are cross-cutting

### Module Guides

Use this section when you already know which domain you are touching.

- what the module owns
- which routes and APIs matter
- where to start debugging

### Operations

Use this section for environment setup, deployment, recovery, and daily commands.

### Reference

Use this section for lookup, not onboarding.

- API surface
- Artisan commands
- theme contract
- generated API reference

## Who Should Read What

- Backend maintainers:
  start with Quickstart, How Pagify Works, System Overview, then the relevant module guide.
- Frontend and admin developers:
  start with Quickstart, How Pagify Works, Admin UI docs, then Page Builder or theme docs.
- Theme developers:
  start with Quickstart, How Pagify Works, then Theme Development and Theme Contract.
- Operators:
  start with Quickstart, Installation and Deployment, then Runbook.

## Documentation Principles

The docs should help a new contributor answer these questions quickly:

- What part of the system owns this behavior?
- Which request, command, or UI flow triggers it?
- Where is the source of truth stored?
- Which files or services should I inspect first?

When updating behavior in code:

- update the relevant module guide in the same pull request
- update operations docs if the workflow changed
- update reference docs only when the contract itself changed

## Local Docs Development

Install docs dependencies:

```bash
cd docs
npm install
```

Start the local docs site:

```bash
cd docs
npm run start
```

Build the static site:

```bash
cd docs
npm run build
```

If you only read one page after this one, read [How Pagify Works](./architecture/how-pagify-works.md).
