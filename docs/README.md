# Pagify Docs Workspace

This folder hosts the Docusaurus documentation site for Pagify.

## Installation

```bash
cd docs
npm install
```

## Local Development

```bash
cd docs
npm run start
```

Default local URL: `http://localhost:3000/pagify/`

## Build

```bash
cd docs
npm run build
```

## Serve Build Output

```bash
cd docs
npm run serve
```

## Deployment

This workspace is configured for GitHub Pages with:

- `url`: `https://omaicode.github.io`
- `baseUrl`: `/pagify/`

CI workflows should build from `docs/` and publish `docs/build/`.
