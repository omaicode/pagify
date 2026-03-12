<?php

namespace Pagify\Core\Services;

use Illuminate\Support\Facades\Log;
use Pagify\Core\Services\ThemeHelpers\AssetThemeHelper;
use Pagify\Core\Services\ThemeHelpers\FormatThemeHelper;
use Pagify\Core\Services\ThemeHelpers\I18nThemeHelper;
use Pagify\Core\Services\ThemeHelpers\SettingsThemeHelper;
use Pagify\Core\Services\ThemeHelpers\ThemeHelpersAccessor;
use Pagify\Core\Services\ThemeHelpers\UrlThemeHelper;
use Twig\Environment;
use Twig\Error\Error as TwigError;
use Twig\Extension\SandboxExtension;
use Twig\Extra\String\StringExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Sandbox\SecurityPolicy;
use Twig\TwigFunction;

class FrontendThemeTwigEngine
{
    public function __construct(
        private readonly ThemeHelperRegistry $helpers,
    ) {
    }

    /**
     * @param array<int, string> $viewPaths
     * @param array<string, mixed> $context
     */
    public function render(array $viewPaths, string $template, array $context = []): ?string
    {
        if ($viewPaths === []) {
            return null;
        }

        try {
            $loader = new FilesystemLoader($viewPaths);
            $twig = new Environment($loader, [
                'autoescape' => 'html',
                'cache' => $this->cachePath(),
                'strict_variables' => (bool) config('core.frontend_ui.render.strict_variables', true),
                'debug' => (bool) config('app.debug', false),
            ]);

            $twig->addExtension(new StringExtension());

            $functions = $this->helpers->globalFunctions();

            foreach ($functions as $name => $callback) {
                $twig->addFunction(new TwigFunction($name, $callback));
            }

            if ((bool) config('core.frontend_ui.render.sandbox.enabled', true)) {
                $twig->addExtension(new SandboxExtension($this->securityPolicy(array_keys($functions)), true));
            }

            $twig->addGlobal('helpers', $this->helpers->helpersAccessor());

            return $twig->render($template, $context);
        } catch (TwigError $exception) {
            Log::warning('Frontend Twig render failed.', [
                'template' => $template,
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function cachePath(): string|false
    {
        if (! (bool) config('core.frontend_ui.render.cache.enabled', true)) {
            return false;
        }

        return (string) config('core.frontend_ui.render.cache.path', storage_path('framework/cache/twig'));
    }

    /**
     * @param array<int, string> $customFunctionNames
     */
    private function securityPolicy(array $customFunctionNames): SecurityPolicy
    {
        $tags = (array) config('core.frontend_ui.render.sandbox.allowed_tags', [
            'if',
            'for',
            'set',
            'block',
            'extends',
            'include',
            'with',
            'apply',
        ]);

        $filters = (array) config('core.frontend_ui.render.sandbox.allowed_filters', [
            'default',
            'escape',
            'e',
            'raw',
            'upper',
            'lower',
            'title',
            'capitalize',
            'length',
            'join',
            'trim',
            'replace',
            'slice',
            'nl2br',
            'striptags',
            'date',
            'u',
        ]);

        $functions = array_values(array_unique(array_merge(
            $customFunctionNames,
            (array) config('core.frontend_ui.render.sandbox.allowed_functions', [])
        )));

        return new SecurityPolicy(
            $tags,
            $filters,
            [
                AssetThemeHelper::class => ['url'],
                UrlThemeHelper::class => ['page', 'site'],
                I18nThemeHelper::class => ['t'],
                FormatThemeHelper::class => ['date'],
                SettingsThemeHelper::class => ['get'],
            ],
            [
                ThemeHelpersAccessor::class => ['asset', 'url', 'i18n', 'format', 'settings'],
            ],
            $functions,
        );
    }
}
