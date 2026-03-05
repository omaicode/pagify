## Core module integration notes

### Hook subscribers (P2)

Core exposes an Event Bus + Hook registry. Other modules can subscribe through the `CoreHookSubscriber` contract:

1. Implement `Pagify\Core\Contracts\CoreHookSubscriber`.
2. Register hooks inside `register(EventBus $eventBus)`.
3. Add subscriber class to `modules/core/config/core.php` under `hook_subscribers`.

Example pattern:

```php
<?php

namespace Pagify\Blog\Hooks;

use Pagify\Core\Contracts\CoreHookSubscriber;
use Pagify\Core\Services\EventBus;

class BlogHookSubscriber implements CoreHookSubscriber
{
	public function register(EventBus $eventBus): void
	{
		$eventBus->onHook('entry.created', function ($event): void {
			// react to shared core hook
		});
	}
}
```

### Admin i18n and locale

- Admin locale is resolved in this order: authenticated admin locale -> site locale -> core default locale.
- Locale switch endpoint: `POST /admin/locale`.
- Inertia shared props include:
  - `locale`
  - `supportedLocales`
  - `localeUpdateUrl`
  - `translations.ui`

UI components should consume `translations.ui` instead of hardcoded strings where possible.
