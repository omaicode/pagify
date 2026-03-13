<?php

namespace Pagify\Installer\Services;

use Illuminate\Support\Facades\File;

class EnvironmentWriter
{
    /**
     * @param array<string, string> $values
     */
    public function write(array $values): void
    {
        $envPath = base_path('.env');

        if (! is_file($envPath)) {
            File::copy(base_path('.env.example'), $envPath);
        }

        $content = (string) file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $content = $this->upsert($content, $key, $this->quoteIfNeeded($value));
        }

        File::put($envPath, $content);
    }

    private function upsert(string $content, string $key, string $value): string
    {
        $pattern = '/^'.preg_quote($key, '/').'=.*/m';
        $line = sprintf('%s=%s', $key, $value);

        if (preg_match($pattern, $content) === 1) {
            return (string) preg_replace($pattern, $line, $content);
        }

        return rtrim($content)."\n".$line."\n";
    }

    private function quoteIfNeeded(string $value): string
    {
        $needsQuote = str_contains($value, ' ') || str_contains($value, '#') || str_contains($value, '"');

        if (! $needsQuote) {
            return $value;
        }

        return '"'.str_replace('"', '\\"', $value).'"';
    }
}
