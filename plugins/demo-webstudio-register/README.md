# Demo Webstudio Register Plugin

This plugin demonstrates Register Component integration for Webstudio using a PHP definition file.

## Files

- plugin.json
- config/webstudio-components.php

## How It Works

When plugin state is enabled, page-builder discovers component definitions from:

- config/webstudio-components.php

Definitions are normalized into page-builder registry output and exposed in /data/{projectId} as registeredComponents.

## Enable Plugin (API)

Use admin plugin APIs to set plugin state enabled, then reopen page-builder editor.
