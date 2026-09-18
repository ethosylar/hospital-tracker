<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Support\AuditLogAccess;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'site_id' => ['nullable', 'integer', 'exists:lt_sites,id',],
            'search' => ['nullable', 'string', 'max:255',],
            'entity_type' => ['nullable', 'string', 'max:50',],
            'entity_id' => ['nullable', 'integer', 'min:1',],
            'action' => ['nullable', 'string', 'max:30',],
            'user_id' => ['nullable', 'integer', 'exists:users,id',],
            'source' => ['nullable', 'string', 'max:30',],
            'from' => ['nullable', 'date',],
            'to' => ['nullable', 'date', 'after_or_equal:from',],
            'page' => ['nullable', 'integer', 'min:1',],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100',],
        ]);

        $query = AuditLogAccess::visibleQuery($request->user())
            ->with([
                'site:id,code,name,short_name',
                'user:id,name,email',
            ]);

        if (!empty($data['site_id'])) {
            $query->where('dt_audit_logs.site_id', (int) $data['site_id']);
        }

        if (!empty($data['entity_type'])) {
            $query->where('entity_type', strtoupper(trim($data['entity_type'])));
        }

        if (!empty($data['entity_id'])) {
            $query->where('entity_id', (int) $data['entity_id']);
        }

        if (!empty($data['action'])) {
            $query->where('action', strtoupper(trim($data['action'])));
        }

        if (!empty($data['user_id'])) {
            $query->where('performed_by_user_id', (int) $data['user_id']);
        }

        if (!empty($data['source'])) {
            $query->where('source', strtoupper(trim($data['source'])));
        }

        if (!empty($data['from'])) {
            $query->whereDate('performed_at', '>=', $data['from']);
        }

        if (!empty($data['to'])) {
            $query->whereDate('performed_at', '<=', $data['to']);
        }

        if (!empty($data['search'])) {
            $search = trim($data['search']);

            $query->where(
                function ($where) use (
                    $search
                ) {
                    $where
                        ->where('entity_type', 'like', "%{$search}%")
                        ->orWhere('action', 'like', "%{$search}%")
                        ->orWhere('source', 'like', "%{$search}%")
                        ->orWhere('changes', 'like', "%{$search}%")
                        ->orWhereHas(
                            'user',
                            function ($userQuery) use ($search) {
                                $userQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            }
                        )
                        ->orWhereHas(
                            'site',
                            function ($siteQuery) use ($search) {
                                $siteQuery
                                    ->where('code', 'like', "%{$search}%")
                                    ->orWhere('name', 'like', "%{$search}%");
                            }
                        );
                }
            );
        }

        $perPage = max(1, min((int) ($data['per_page'] ?? 20), 100));

        return AuditLogResource::collection(
            $query
                ->orderByDesc('performed_at')
                ->orderByDesc('id')
                ->paginate($perPage)
        );
    }

    public function show(Request $request, int $id)
    {
        $auditLog = AuditLogAccess::visibleQuery($request->user())
            ->with([
                'site:id,code,name,short_name',
                'user:id,name,email',
            ])
            ->findOrFail($id);

        return new AuditLogResource($auditLog);
    }
}
