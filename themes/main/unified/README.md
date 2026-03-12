# Unified Theme

Unified is the default multipurpose frontend theme for Pagify CMS.

## Highlights

- Tailwind-first structure for rapid page composition
- Three color presets: Ocean, Forest, Sunset
- Responsive layout for desktop and mobile
- Suitable for product intro sites and documentation landing pages

## Assets

- CSS: assets/css/main.css
- JS: assets/js/theme-switcher.js

## Build Tailwind (No CDN)

Run from `themes/main/unified`:

```bash
npm install
npm run build
```

Watch mode:

```bash
npm run dev
```

Tailwind input file: `src/css/app.css`
Build output file: `assets/css/main.css`

## Page Entry

- Layout: layouts/app.twig
- Page view: pages/home.twig
