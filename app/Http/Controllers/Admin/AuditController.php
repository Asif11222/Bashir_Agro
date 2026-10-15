<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AuditController extends Controller
{
    /**
     * Display a listing of audit trail logs with filtering, searching, and pagination.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $module = $request->query('module');
        $action = $request->query('action');
        $userId = $request->query('user_id');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $query = AuditLog::with('user')->latest('created_at');

        // Module filter
        if ($module && $module !== 'all') {
            $query->where('module', $module);
        }

        // Action filter
        if ($action && $action !== 'all') {
            $query->where('action', $action);
        }

        // User filter
        if ($userId && $userId !== 'all') {
            $query->where('user_id', $userId);
        }

        // Date range filter
        if ($startDate) {
            $query->where('created_at', '>=', Carbon::parse($startDate)->startOfDay());
        }
        if ($endDate) {
            $query->where('created_at', '<=', Carbon::parse($endDate)->endOfDay());
        }

        // Driver-aware search by User name, Module, Description, Action
        if ($search !== '') {
            $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            $query->where(function ($q) use ($search, $likeOperator) {
                $q->where('description', $likeOperator, "%{$search}%")
                    ->orWhere('action', $likeOperator, "%{$search}%")
                    ->orWhere('module', $likeOperator, "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search, $likeOperator) {
                        $uq->where('name', $likeOperator, "%{$search}%")
                            ->orWhere('email', $likeOperator, "%{$search}%");
                    });
            });
        }

        $logs = $query->paginate(20)->withQueryString();

        // Get filter options for dropdowns
        $users = User::orderBy('name')->get();
        $availableModules = AuditLog::distinct()->pluck('module')->filter()->values();
        $availableActions = AuditLog::distinct()->pluck('action')->filter()->values();

        return view('audit.index', [
            'activeTab' => 'audit',
            'title' => 'অডিট ট্রেইল লগ',
            'logs' => $logs,
            'users' => $users,
            'availableModules' => $availableModules,
            'availableActions' => $availableActions,
            'filters' => [
                'search' => $search,
                'module' => $module,
                'action' => $action,
                'user_id' => $userId,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }
}
