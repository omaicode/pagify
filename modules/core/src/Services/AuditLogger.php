<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Request;
use Modules\Core\Models\AuditLog;
use Modules\Core\Support\SiteContext;
use Throwable;

class AuditLogger
{
    private const DEFAULT_REDACTION_MASK = '[REDACTED]';

    /**
     * @var array<int, string>
     */
    private array $sensitiveKeys;

    private string $redactionMask;

    public function __construct(
        private readonly SiteContext $siteContext,
        private readonly AuthFactory $auth,
    ) {
        $configuredKeys = config('core.audit.redact_keys', [
            'password',
            'password_confirmation',
            'token',
            'secret',
            'authorization',
            'cookie',
            'api_key',
            'remember_token',
        ]);

        $this->sensitiveKeys = collect(is_array($configuredKeys) ? $configuredKeys : [])
            ->filter(static fn (mixed $key): bool => is_string($key) && $key !== '')
            ->map(static fn (string $key): string => strtolower($key))
            ->values()
            ->all();

        $mask = config('core.audit.redaction_mask', self::DEFAULT_REDACTION_MASK);
        $this->redactionMask = is_string($mask) && $mask !== '' ? $mask : self::DEFAULT_REDACTION_MASK;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(string $action, string $entityType, string|int|null $entityId = null, array $metadata = []): AuditLog
    {
        $userId = $this->auth->guard('web')->id();
        $redactedMetadata = $this->redact($metadata);

        try {
            return AuditLog::query()->create([
                'site_id' => $this->siteContext->siteId(),
                'admin_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId !== null ? (string) $entityId : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'metadata' => $redactedMetadata,
            ]);
        } catch (Throwable) {
            return new AuditLog([
                'site_id' => $this->siteContext->siteId(),
                'admin_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId !== null ? (string) $entityId : null,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'metadata' => $redactedMetadata,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    private function redact(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            $sanitized[$key] = $this->sanitizeValue((string) $key, $value);
        }

        return $sanitized;
    }

    private function sanitizeValue(string $key, mixed $value): mixed
    {
        if ($this->isSensitiveKey($key)) {
            return $this->redactionMask;
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $nestedKey => $nestedValue) {
                $resolvedKey = is_string($nestedKey) ? $nestedKey : (string) $nestedKey;
                $sanitized[$nestedKey] = $this->sanitizeValue($resolvedKey, $nestedValue);
            }

            return $sanitized;
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower($key);

        foreach ($this->sensitiveKeys as $sensitiveKey) {
            if ($normalized === $sensitiveKey || str_contains($normalized, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
