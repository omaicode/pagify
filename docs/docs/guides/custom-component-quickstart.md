---
sidebar_position: 2
title: CustomComponent Quickstart
---

# CustomComponent Quickstart

This guide focuses on direct, actionable steps to create a CustomComponent for Webstudio.

## 7 Quick Steps

1. Create a component class in your module or plugin.
2. Implement the `Pagify\PageBuilder\Webstudio\Contracts\CustomComponent` interface.
3. Return the definition with `ComponentDefinitionBuilder`.
4. Register the class in `webstudio_components` in module/plugin config.
5. Run the validation command.
6. Call the data API and verify the `registeredComponents` payload.
7. Open the editor and drag the component from the Components tab.

## Step 1: Create the Component Class

Example in a plugin:

`plugins/demo-webstudio-register/src/Webstudio/Components/UserListComponent.php`

```php
<?php

namespace Plugins\DemoWebstudioRegister\Webstudio\Components;

use Pagify\PageBuilder\Webstudio\Contracts\CustomComponent;
use Pagify\PageBuilder\Webstudio\Support\ComponentDefinitionBuilder;

class UserListComponent implements CustomComponent
{
    public function definition(): array
    {
        return ComponentDefinitionBuilder::make('user-list', 'User List')
            ->description('User list')
            ->icon('👥')
            ->category('Data Components')
            ->element('section')
            ->classes(['user-list', 'user-list--card'])
            ->attribute('data-component', 'user-list')
            ->text('User list placeholder')
            ->toArray();
    }
}
```

## Step 2: Register in Config

### Module

`modules/{module-slug}/config/module.php`

```php
<?php

use Vendor\YourModule\Webstudio\Components\UserListComponent;

return [
    'webstudio_components' => [
        UserListComponent::class,
    ],
];
```

### Plugin

`plugins/{plugin-slug}/config/plugin.php`

```php
<?php

use Plugins\DemoWebstudioRegister\Webstudio\Components\UserListComponent;

return [
    'webstudio_components' => [
        UserListComponent::class,
    ],
];
```

## Step 3: Validate

```bash
php artisan cms:page-builder:validate-webstudio-components
```

If you need JSON output:

```bash
php artisan cms:page-builder:validate-webstudio-components --json
```

## Step 4: Verify Payload

Call the data API:

`GET /api/v1/{admin_prefix}/page-builder/data/{projectId}`

Check these fields in `registeredComponents`:

- `key`
- `label`
- `owner`
- `owner_type`
- `source`
- `html_template`

## Step 5: Verify in Editor UI

- Open the editor page.
- Go to the Components tab.
- Find the group by owner.
- Drag the new component onto the canvas.

## Common Definition Example

```php
return ComponentDefinitionBuilder::make('hero-banner', 'Hero Banner')
    ->description('Top hero section')
    ->element('section')
    ->classes(['hero', 'hero--primary'])
    ->styles([
        'padding' => '24px',
        'border-radius' => '12px',
    ])
    ->attribute('data-variant', 'hero')
    ->text('Hero Banner')
    ->toArray();
```

## Dynamic Placeholder Example

```php
return ComponentDefinitionBuilder::make('hero-banner', 'Hero Banner')
    ->dynamicData([
        'summary' => 'Page: {{ page.title }}',
    ])
    ->description('Dynamic summary: {{ dynamic.summary }}')
    ->attribute('data-page', '{{ page.slug }}')
    ->text('Welcome to {{ page.title }}')
    ->toArray();
```

Supported placeholders:

- `{{ page.* }}`
- `{{ project.* }}`
- `{{ runtime.* }}`
- `{{ dynamic.* }}`
- `{{ now }}`

## Pre-merge Checklist

- Component class correctly implements `CustomComponent`.
- Class is registered in `webstudio_components`.
- Validation command passes.
- Data API includes the new component in `registeredComponents`.
- Component can be dragged and rendered correctly in the editor.
