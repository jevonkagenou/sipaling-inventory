<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ForecastingLog;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * Bangun query audit log berdasarkan filter aktif dari request.
     */
    private function buildQuery(Request $request)
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

        if ($request->filled('causer_id') && $request->input('causer_id') !== 'all') {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        } elseif ($request->filled('start_date')) {
            $query->where('created_at', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        } elseif ($request->filled('end_date')) {
            $query->where('created_at', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        return $query;
    }

    /**
     * Tampilkan Daftar Jejak Audit & Investigasi Forensik
     */
    public function index(Request $request): Response
    {
        $query = $this->buildQuery($request);

        $logs = $query->latest()->paginate(15)->withQueryString();

        $baseStatsQuery = ActivityLog::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $baseStatsQuery->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        } elseif ($request->filled('start_date')) {
            $baseStatsQuery->where('created_at', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        } elseif ($request->filled('end_date')) {
            $baseStatsQuery->where('created_at', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        if ($request->filled('causer_id') && $request->input('causer_id') !== 'all') {
            $baseStatsQuery->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $baseStatsQuery->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%")
                    ->orWhereHas('causer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $forecastLogQuery = ForecastingLog::query();
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $forecastLogQuery->whereBetween('created_at', [
                Carbon::parse($request->input('start_date'))->startOfDay(),
                Carbon::parse($request->input('end_date'))->endOfDay(),
            ]);
        } elseif ($request->filled('start_date')) {
            $forecastLogQuery->where('created_at', '>=', Carbon::parse($request->input('start_date'))->startOfDay());
        } elseif ($request->filled('end_date')) {
            $forecastLogQuery->where('created_at', '<=', Carbon::parse($request->input('end_date'))->endOfDay());
        }

        $isFiltered = $request->filled('start_date') || $request->filled('end_date') || ($request->filled('causer_id') && $request->input('causer_id') !== 'all') || $request->filled('search');

        $stats = [
            'total_logs' => (clone $baseStatsQuery)->count(),
            'inventory_logs' => (clone $baseStatsQuery)->where('log_name', 'inventory')->count(),
            'user_logs' => (clone $baseStatsQuery)->where('log_name', 'users')->count(),
            'forecast_logs' => $forecastLogQuery->count(),
            'is_filtered' => $isFiltered,
        ];

        $users = \App\Models\User::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::render('Audit/Index', [
            'logs' => $logs,
            'stats' => $stats,
            'users' => $users,
            'filters' => [
                'search' => $request->input('search', ''),
                'event' => $request->input('event', ''),
                'log_name' => $request->input('log_name', ''),
                'causer_id' => $request->input('causer_id', ''),
                'start_date' => $request->input('start_date', ''),
                'end_date' => $request->input('end_date', ''),
            ],
        ]);
    }

    /**
     * Ekspor Log Audit ke berkas CSV atau PDF (dengan filter aktif dari URL)
     */
    public function export(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        $format = strtolower($request->query('format', 'csv'));

        $logs = $this->buildQuery($request)->latest()->get();

        $dateStr = now()->format('Y-m-d');
        $filterDesc = $this->buildFilterDescription($request);

        // Rekam jejak audit ekspor
        ActivityLog::create([
            'log_name'     => 'audit',
            'description'  => 'Mengekspor rekam jejak audit log format ' . strtoupper($format) . ($filterDesc ? " ({$filterDesc})" : ''),
            'causer_type'  => Auth::user() ? get_class(Auth::user()) : null,
            'causer_id'    => Auth::id(),
            'event'        => 'exported',
            'properties'   => [
                'format'      => $format,
                'total_items' => $logs->count(),
                'filters'     => [
                    'search'     => $request->input('search'),
                    'event'      => $request->input('event'),
                    'log_name'   => $request->input('log_name'),
                    'causer_id'  => $request->input('causer_id'),
                    'start_date' => $request->input('start_date'),
                    'end_date'   => $request->input('end_date'),
                ],
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        ]);

        // --- Format CSV ---
        if ($format === 'csv') {
            $filename = "audit_log_sipaling_{$dateStr}.csv";
            $headers  = [
                'Content-Type'        => 'text/csv; charset=UTF-8',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Pragma'              => 'no-cache',
                'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
                'Expires'             => '0',
            ];

            $callback = function () use ($logs) {
                $handle = fopen('php://output', 'w');
                // UTF-8 BOM agar Excel membaca karakter Indonesia dengan benar
                fputs($handle, "\xEF\xBB\xBF");

                fputcsv($handle, [
                    'No',
                    'Waktu & Tanggal',
                    'Kategori (Log Name)',
                    'Tipe Event',
                    'Deskripsi Aktivitas',
                    'Pengguna (Aktor)',
                    'Email Pengguna',
                ]);

                $no = 1;
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $no++,
                        $log->created_at?->setTimezone('Asia/Jakarta')->format('d M Y, H:i:s') ?? '-',
                        $log->log_name ?? '-',
                        strtoupper($log->event ?? '-'),
                        $log->description ?? '-',
                        $log->causer?->name ?? 'Sistem / Anonim',
                        $log->causer?->email ?? '-',
                    ]);
                }

                fclose($handle);
            };

            return response()->stream($callback, 200, $headers);
        }

        // --- Format PDF (DomPDF) ---
        $filename = "audit_log_sipaling_{$dateStr}.pdf";

        $exportedBy = Auth::user()?->name ?? 'Sistem';
        $exportedAt = now()->setTimezone('Asia/Jakarta')->isoFormat('D MMMM YYYY, HH:mm [WIB]');

        $pdf = Pdf::loadView('exports.audit-log-pdf', [
            'logs'        => $logs,
            'exportedBy'  => $exportedBy,
            'exportedAt'  => $exportedAt,
            'filterDesc'  => $filterDesc ?: 'Seluruh rekam jejak',
            'totalItems'  => $logs->count(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download($filename);
    }

    /**
     * Bangun deskripsi teks filter aktif untuk header laporan.
     */
    private function buildFilterDescription(Request $request): string
    {
        $parts = [];

        if ($request->filled('search')) {
            $parts[] = 'Kata kunci: "' . $request->input('search') . '"';
        }
        if ($request->filled('event') && $request->input('event') !== 'all') {
            $parts[] = 'Event: ' . strtoupper($request->input('event'));
        }
        if ($request->filled('log_name') && $request->input('log_name') !== 'all') {
            $parts[] = 'Kategori: ' . $request->input('log_name');
        }
        if ($request->filled('causer_id') && $request->input('causer_id') !== 'all') {
            $user = \App\Models\User::find($request->input('causer_id'));
            $parts[] = 'Pengguna: ' . ($user?->name ?? $request->input('causer_id'));
        }
        if ($request->filled('start_date')) {
            $parts[] = 'Dari: ' . $request->input('start_date');
        }
        if ($request->filled('end_date')) {
            $parts[] = 'S.d.: ' . $request->input('end_date');
        }

        return implode(' | ', $parts);
    }
}
