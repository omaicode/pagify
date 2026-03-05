<?php

namespace Pagify\Core\Http\Controllers\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Pagify\Core\Models\Admin;
use Pagify\Core\Models\AuditLog;
use Pagify\Core\Models\Site;
use Pagify\Core\Support\SiteContext;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:180'],
            'action' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:120'],
            'entity_id' => ['nullable', 'string', 'max:120'],
            'site_id' => ['nullable', 'integer'],
            'admin_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'in:10,20,50,100'],
            'sort_by' => ['nullable', 'string', 'in:id,action,entity_type,created_at'],
            'sort_dir' => ['nullable', 'string', 'in:asc,desc'],
        ]);

        $query = AuditLog::query()->with(['admin:id,name,username', 'site:id,name,domain']);
        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $sortDir = (string) ($filters['sort_dir'] ?? 'desc');
        $perPage = (int) ($filters['per_page'] ?? 20);

        if (($filters['q'] ?? null) !== null && trim((string) $filters['q']) !== '') {
            $q = trim((string) $filters['q']);
            $query->where(function (Builder $builder) use ($q): void {
                $builder
                    ->where('action', 'like', '%' . $q . '%')
                    ->orWhere('entity_type', 'like', '%' . $q . '%')
                    ->orWhere('entity_id', 'like', '%' . $q . '%')
                    ->orWhere('ip_address', 'like', '%' . $q . '%');
            });
        }

        if (($filters['action'] ?? null) !== null && $filters['action'] !== '') {
            $query->where('action', (string) $filters['action']);
        }

        if (($filters['entity_type'] ?? null) !== null && $filters['entity_type'] !== '') {
            $query->where('entity_type', (string) $filters['entity_type']);
        }

        if (($filters['entity_id'] ?? null) !== null && $filters['entity_id'] !== '') {
            $query->where('entity_id', 'like', '%' . (string) $filters['entity_id'] . '%');
        }

        if (($filters['site_id'] ?? null) !== null) {
            $query->where('site_id', (int) $filters['site_id']);
        }

        if (($filters['admin_id'] ?? null) !== null) {
            $query->where('admin_id', (int) $filters['admin_id']);
        }

        if (($filters['date_from'] ?? null) !== null) {
            $query->whereDate('created_at', '>=', (string) $filters['date_from']);
        }

        if (($filters['date_to'] ?? null) !== null) {
            $query->whereDate('created_at', '<=', (string) $filters['date_to']);
        }

        $query->orderBy($sortBy, $sortDir)->orderByDesc('id');

        $auditLogs = $query
            ->paginate($perPage)
            ->withQueryString()
            ->through(static fn (AuditLog $auditLog): array => [
                'id' => $auditLog->id,
                'action' => $auditLog->action,
                'entity_type' => $auditLog->entity_type,
                'entity_id' => $auditLog->entity_id,
                'ip_address' => $auditLog->ip_address,
                'admin' => $auditLog->admin?->only(['id', 'name', 'username']),
                'site' => $auditLog->site?->only(['id', 'name', 'domain']),
                'created_at' => optional($auditLog->created_at)?->toDateTimeString(),
            ]);

        $filterOptions = [
            'actions' => AuditLog::query()
                ->select('action')
                ->distinct()
                ->orderBy('action')
                ->pluck('action')
                ->values(),
            'entity_types' => AuditLog::query()
                ->select('entity_type')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type')
                ->values(),
            'sites' => Site::query()
                ->select(['id', 'name'])
                ->when(app(SiteContext::class)->siteId() !== null, static function (Builder $builder): void {
                    $builder->where('id', app(SiteContext::class)->siteId());
                })
                ->orderBy('name')
                ->get(),
            'admins' => Admin::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ];

        return Inertia::render('Admin/Audit/Index', [
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'auditLogs' => $auditLogs,
        ]);
    }
}
