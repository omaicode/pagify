<?php

namespace Pagify\Core\Services\ThemeHelpers;

use Carbon\Carbon;
use DateTimeInterface;

class FormatThemeHelper
{
    public function date(mixed $value, string $format = 'Y-m-d H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            if ($value instanceof DateTimeInterface) {
                return Carbon::instance($value)->format($format);
            }

            return Carbon::parse($value)->format($format);
        } catch (\Throwable) {
            return '';
        }
    }
}
