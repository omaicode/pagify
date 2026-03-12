<?php

namespace Pagify\Core\Services;

use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Pagify\Core\Services\ThemeHelpers\FormatThemeHelper;
use Pagify\Core\Services\ThemeHelpers\I18nThemeHelper;
use Pagify\Core\Services\ThemeHelpers\SettingsThemeHelper;
use Pagify\Core\Services\ThemeHelpers\ThemeHelpersAccessor;
use Pagify\Core\Services\ThemeHelpers\UrlThemeHelper;

class ThemeHelperRegistry
{
    public function __construct(
        private readonly EventBus $eventBus,
        private readonly AssetThemeHelper $assetHelper,
        private readonly UrlThemeHelper $urlHelper,
        private readonly I18nThemeHelper $i18nHelper,
        private readonly FormatThemeHelper $formatHelper,
        private readonly SettingsThemeHelper $settingsHelper,
    ) {
    }

    /**
     * @return array<string, callable>
     */
    public function globalFunctions(): array
    {
        $functions = [
            'asset_url' => fn (string $path = ''): string => $this->assetHelper->url($path),
            'page_url' => fn (string $slug = ''): string => $this->urlHelper->page($slug),
            'site_url' => fn (string $path = ''): string => $this->urlHelper->site($path),
            't' => fn (string $key, array $replace = []): string => $this->i18nHelper->t($key, $replace),
            'format_date' => fn (mixed $value, string $format = 'Y-m-d H:i'): string => $this->formatHelper->date($value, $format),
            'setting' => fn (string $key, mixed $default = null): mixed => $this->settingsHelper->get($key, $default),
        ];

        foreach ($this->eventBus->emitHook('theme.render.helpers') as $extra) {
            if (! is_array($extra) || ! isset($extra['global']) || ! is_array($extra['global'])) {
                continue;
            }

            foreach ($extra['global'] as $name => $callback) {
                if (! is_string($name) || $name === '' || ! is_callable($callback)) {
                    continue;
                }

                $functions[$name] = $callback;
            }
        }

        return $functions;
    }

    public function helpersAccessor(): ThemeHelpersAccessor
    {
        return new ThemeHelpersAccessor(
            $this->assetHelper,
            $this->urlHelper,
            $this->i18nHelper,
            $this->formatHelper,
            $this->settingsHelper,
        );
    }
}
