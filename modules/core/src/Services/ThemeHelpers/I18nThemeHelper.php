<?php

namespace Pagify\Core\Services\ThemeHelpers;

class I18nThemeHelper
{
    /**
     * @param array<string, mixed> $replace
     */
    public function t(string $key, array $replace = []): string
    {
        return (string) __($key, $replace);
    }
}
