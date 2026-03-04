<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogIndexRequest;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;

class AuditLogController extends Controller
{
    public function index(AuditLogIndexRequest $request)
    {
        $v = $request->validated();

        $q = AuditLog::query()
            ->with(['user:id,name,email']);

        // Filters
        if (!empty($v['entity_type'])) {
            $q->where('entity_type', $v['entity_type']);
        }

        if (!empty($v['entity_id'])) {
            $q->where('entity_id', (int) $v['entity_id']);
        }

        if (!empty($v['action'])) {
            $q->where('action', $v['action']);
        }

        if (!empty($v['user_id'])) {
            $q->where('performed_by_user_id', (int) $v['user_id']);
        }

        // Date range (performed_at)
        if (!empty($v['from'])) {
            $q->whereDate('performed_at', '>=', $v['from']);
        }
        if (!empty($v['to'])) {
            $q->whereDate('performed_at', '<=', $v['to']);
        }

        // Lightweight search
        if (!empty($v['search'])) {
            $s = $v['search'];
            $q->where(function ($w) use ($s) {
                $w->where('entity_type', 'like', "%{$s}%")
                  ->orWhere('action', 'like', "%{$s}%")
                  ->orWhere('source', 'like', "%{$s}%");
            });
        }

        $perPage = (int)($v['per_page'] ?? 20);

        $page = $q->orderByDesc('performed_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'ok' => true,
            'data' => AuditLogResource::collection($page->items()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'per_page'     => $page->perPage(),
                'total'        => $page->total(),
                'last_page'    => $page->lastPage(),
            ],
        ]);
    }

    public function show($id)
    {
        $row = AuditLog::query()
            ->with(['user:id,name,email'])
            ->findOrFail($id);

        return response()->json([
            'ok' => true,
            'data' => new AuditLogResource($row),
        ]);
    }
}
