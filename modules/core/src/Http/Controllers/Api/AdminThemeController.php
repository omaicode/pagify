<?php

namespace Pagify\Core\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Pagify\Core\Http\Requests\Admin\ActivateFrontendThemeRequest;
use Pagify\Core\Http\Requests\Admin\StoreFrontendThemeRequest;
use Pagify\Core\Http\Requests\Admin\UpdateFrontendThemeRequest;
use Pagify\Core\Models\Admin;
use Pagify\Core\Services\FrontendThemeManagerService;

class AdminThemeController extends ApiController
{
    public function index(FrontendThemeManagerService $themes): JsonResponse
    {
        $this->authorize('manageThemes', Admin::class);

        return $this->success($themes->all());
    }

    public function store(StoreFrontendThemeRequest $request, FrontendThemeManagerService $themes): JsonResponse
    {
        $this->authorize('manageThemes', Admin::class);

        $result = $themes->create($request->validated());

        if (($result['ok'] ?? false) !== true) {
            return $this->error(
                $this->resolveErrorMessage((string) ($result['code'] ?? 'THEME_CREATE_FAILED'), $result),
                (int) ($result['status'] ?? 422),
                (string) ($result['code'] ?? 'THEME_CREATE_FAILED'),
                (array) ($result['errors'] ?? [])
            );
        }

        return $this->success($result['theme'] ?? null, 201);
    }

    public function update(UpdateFrontendThemeRequest $request, string $theme, FrontendThemeManagerService $themes): JsonResponse
    {
        $this->authorize('manageThemes', Admin::class);

        $result = $themes->update($theme, $request->validated());

        if (($result['ok'] ?? false) !== true) {
            return $this->error(
                $this->resolveErrorMessage((string) ($result['code'] ?? 'THEME_UPDATE_FAILED'), $result),
                (int) ($result['status'] ?? 422),
                (string) ($result['code'] ?? 'THEME_UPDATE_FAILED'),
                (array) ($result['errors'] ?? [])
            );
        }

        return $this->success($result['theme'] ?? null);
    }

    public function activate(ActivateFrontendThemeRequest $request, string $theme, FrontendThemeManagerService $themes): JsonResponse
    {
        $this->authorize('manageThemes', Admin::class);

        $result = $themes->activate($theme, $request->validated('site_id'));

        if (($result['ok'] ?? false) !== true) {
            return $this->error(
                $this->resolveErrorMessage((string) ($result['code'] ?? 'THEME_ACTIVATE_FAILED'), $result),
                (int) ($result['status'] ?? 422),
                (string) ($result['code'] ?? 'THEME_ACTIVATE_FAILED'),
                (array) ($result['errors'] ?? [])
            );
        }

        return $this->success([
            'theme' => $result['theme'] ?? null,
            'site_id' => $result['site_id'] ?? null,
        ]);
    }

    public function destroy(string $theme, FrontendThemeManagerService $themes): JsonResponse
    {
        $this->authorize('manageThemes', Admin::class);

        $result = $themes->delete($theme);

        if (($result['ok'] ?? false) !== true) {
            return $this->error(
                $this->resolveErrorMessage((string) ($result['code'] ?? 'THEME_DELETE_FAILED'), $result),
                (int) ($result['status'] ?? 422),
                (string) ($result['code'] ?? 'THEME_DELETE_FAILED'),
                (array) ($result['errors'] ?? [])
            );
        }

        return $this->success(['deleted' => true]);
    }

    /**
     * @param array<string, mixed> $result
     */
    private function resolveErrorMessage(string $code, array $result): string
    {
        return match ($code) {
            'THEME_NOT_FOUND' => __('core::messages.api.theme_not_found'),
            'THEME_ALREADY_EXISTS' => __('core::messages.api.theme_already_exists'),
            'THEME_LOCKED' => __('core::messages.api.theme_locked'),
            'THEME_IN_USE' => __('core::messages.api.theme_in_use', ['count' => (int) (($result['errors']['usage_count'][0] ?? 0))]),
            'THEME_INVALID' => __('core::messages.api.theme_invalid'),
            'THEME_SITE_REQUIRED' => __('core::messages.api.theme_site_required'),
            'THEME_SITE_NOT_FOUND' => __('core::messages.api.theme_site_not_found'),
            default => (string) ($result['message'] ?? __('core::messages.api.http_error')),
        };
    }
}
