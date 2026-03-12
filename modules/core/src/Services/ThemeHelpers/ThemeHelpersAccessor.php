<?php

namespace Pagify\Core\Services\ThemeHelpers;

class ThemeHelpersAccessor
{
    public function __construct(
        public readonly AssetThemeHelper $asset,
        public readonly UrlThemeHelper $url,
        public readonly I18nThemeHelper $i18n,
        public readonly FormatThemeHelper $format,
        public readonly SettingsThemeHelper $settings,
    ) {
    }
}
