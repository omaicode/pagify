<?php

namespace Modules\Core\Services;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Facades\Request;
use Modules\Core\Models\AuditLog;
use Modules\Core\Support\SiteContext;

class AuditLogger
{
    public function __construct(
        private readonly SiteContext $siteContext,
        private readonly AuthFactory $auth,
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function log(string $action, string $entityType, string|int|null $entityId = null, array $metadata = []): AuditLog
    {
        $userId = $this->auth->guard('web')->id();

        return AuditLog::query()->create([
            'site_id' => $this->siteContext->siteId(),
            'admin_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId !== null ? (string) $entityId : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }
}
