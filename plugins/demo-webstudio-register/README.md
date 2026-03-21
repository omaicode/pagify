# Demo Webstudio Register Plugin

This plugin demonstrates Register Component integration for Webstudio using class-based component definitions.

## Files

- plugin.json
- config/plugin.php
- src/Webstudio/Components/HeroBannerComponent.php
- src/Webstudio/Components/CtaStripComponent.php

## How It Works

When plugin state is enabled, page-builder discovers component class references from:

- config/plugin.php (`webstudio_components`)

Each class returns a component definition array (key, label, element/tag, class/style/attributes, ...).
Definitions are normalized into page-builder registry output and exposed in /data/{projectId} as registeredComponents.

## Enable Plugin (API)

Use admin plugin APIs to set plugin state enabled, then reopen page-builder editor.
