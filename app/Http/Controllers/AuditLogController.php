<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ForecastingLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Tampilkan Daftar Jejak Audit & Investigasi Forensik
     */
    public function index(Request $request): Response
    {
        $query = ActivityLog::with('causer:id,name,email');

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('event') && $request->input('event') !== 'all') {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('log_name') && $request->input('log_name') !== 'all') {
            $query->where('log_name', $request->input('log_name'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->input('start_date'));
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->input('end_date'));
        }

        $logs = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'total_logs' => ActivityLog::count(),
            'inventory_logs' => ActivityLog::where('log_name', 'inventory')->count(),
            'user_logs' => ActivityLog::where('log_name', 'users')->count(),
            'forecast_logs' => ForecastingLog::count(),
        ];

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'filters' => [
                'search' => $request->input('search', ''),
                'event' => $request->input('event', ''),
                'log_name' => $request->input('log_name', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
        ]);
    }
}
