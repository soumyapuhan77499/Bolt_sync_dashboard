<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query();

        if ($request->filled('module_name')) {
            $query->where('module_name', $request->module_name);
        }

        if ($request->filled('action_name')) {
            $query->where('action_name', $request->action_name);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('admin_user_id')) {
            $query->where('admin_user_id', 'like', '%' . $request->admin_user_id . '%');
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->keyword);

            $query->where(function ($q) use ($keyword) {
                $q->where('module_name', 'like', "%{$keyword}%")
                    ->orWhere('action_name', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%")
                    ->orWhere('admin_name', 'like', "%{$keyword}%")
                    ->orWhere('admin_user_id', 'like', "%{$keyword}%")
                    ->orWhere('ip_address', 'like', "%{$keyword}%");
            });
        }

        $logs = $query->orderByDesc('id')->paginate(20)->withQueryString();

        $stats = [
            'total_logs' => $this->safeCount(),
            'success_logs' => $this->safeCount('status', 'success'),
            'failed_logs' => $this->safeCount('status', 'failed'),
            'today_logs' => $this->safeTodayCount(),
        ];

        $modules = AuditLog::select('module_name')
            ->whereNotNull('module_name')
            ->distinct()
            ->orderBy('module_name')
            ->pluck('module_name');

        $actions = AuditLog::select('action_name')
            ->whereNotNull('action_name')
            ->distinct()
            ->orderBy('action_name')
            ->pluck('action_name');

        return view('admin.audit.index', compact(
            'logs',
            'stats',
            'modules',
            'actions'
        ));
    }

    private function safeCount(?string $column = null, ?string $value = null): int
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return 0;
            }

            $query = AuditLog::query();

            if ($column && $value !== null && Schema::hasColumn('audit_logs', $column)) {
                $query->where($column, $value);
            }

            return $query->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function safeTodayCount(): int
    {
        try {
            if (!Schema::hasTable('audit_logs')) {
                return 0;
            }

            if (Schema::hasColumn('audit_logs', 'logged_at')) {
                return AuditLog::whereDate('logged_at', now()->toDateString())->count();
            }

            return AuditLog::whereDate('created_at', now()->toDateString())->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}