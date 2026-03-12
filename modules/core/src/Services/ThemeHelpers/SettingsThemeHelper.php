<?php

namespace Pagify\Core\Services\ThemeHelpers;

use Pagify\Core\Services\SettingsManager;

class SettingsThemeHelper
{
    public function __construct(
        private readonly SettingsManager $settingsManager,
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settingsManager->get($key, $default);
    }
}
