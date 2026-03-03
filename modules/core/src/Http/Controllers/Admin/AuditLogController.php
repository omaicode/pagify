<?php

namespace Modules\Core\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Core\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'action' => ['nullable', 'string', 'max:120'],
            'site_id' => ['nullable', 'integer'],
            'admin_id' => ['nullable', 'integer'],
        ]);

        $query = AuditLog::query()->with(['admin:id,name,username', 'site:id,name,domain']);

        if (($filters['q'] ?? null) !== null) {
            $q = (string) $filters['q'];
            $query->where(function ($builder) use ($q): void {
                $builder
                    ->where('action', 'like', '%' . $q . '%')
                    ->orWhere('entity_type', 'like', '%' . $q . '%')
                    ->orWhere('entity_id', 'like', '%' . $q . '%');
            });
        }

        if (($filters['action'] ?? null) !== null) {
            $query->where('action', $filters['action']);
        }

        if (($filters['site_id'] ?? null) !== null) {
            $query->where('site_id', (int) $filters['site_id']);
        }

        if (($filters['admin_id'] ?? null) !== null) {
            $query->where('admin_id', (int) $filters['admin_id']);
        }

        $auditLogs = $query
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(static fn (AuditLog $auditLog): array => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'admin' => $auditLog->admin?->only(['id', 'name', 'username']),
                'site' => $auditLog->site?->only(['id', 'name', 'domain']),
                'created_at' => optional($auditLog->created_at)?->toDateTimeString(),
            ]);

        return Inertia::render('Admin/Audit/Index', [
            'filters' => $filters,
            'auditLogs' => $auditLogs,
        ]);
    }
}
