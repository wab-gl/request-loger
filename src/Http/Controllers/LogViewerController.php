<?php

namespace GreeLogix\RequestLogger\Http\Controllers;

use GreeLogix\RequestLogger\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class LogViewerController extends Controller
{
    /**
     * Display a paginated list of request logs with search.
     */
    public function index(Request $request)
    {
        $query = RequestLog::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('path', 'like', "%{$search}%")
                    ->orWhere('method', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhere('status_code', 'like', "%{$search}%")
                    ->orWhere('user_id', 'like', "%{$search}%")
                    ->orWhereRaw('CAST(headers AS CHAR) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('CAST(body AS CHAR) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('CAST(response_body AS CHAR) LIKE ?', ["%{$search}%"]);
            });
        }

        // Filter by method
        if ($request->filled('method')) {
            $query->where('method', $request->get('method'));
        }

        // Filter by status code
        if ($request->filled('status_code')) {
            $query->where('status_code', $request->get('status_code'));
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->get('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->get('date_to'));
        }

        $perPage = config('gl-request-logger.per_page', 50);
        $logs = $query->with('user')->orderByDesc('created_at')->paginate($perPage)->withQueryString();
        
        // Get the latest log ID for polling
        $latestLog = RequestLog::orderByDesc('id')->first();

        return view('gl-request-logger::index', [
            'logs' => $logs,
            'search' => $request->get('search'),
            'method' => $request->get('method'),
            'status_code' => $request->get('status_code'),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'latest_log_id' => $latestLog ? $latestLog->id : 0,
            'slow_threshold' => config('gl-request-logger.slow_request_threshold_ms', 1000),
        ]);
    }

    /**
     * Display a single request log detail.
     */
    public function show($id)
    {
        $log = RequestLog::with('user')->findOrFail($id);

        return view('gl-request-logger::show', [
            'log' => $log,
            'slow_threshold' => config('gl-request-logger.slow_request_threshold_ms', 1000),
        ]);
    }

    /**
     * Clear all request logs.
     */
    public function destroy(Request $request)
    {
        $deleted = RequestLog::query()->delete();

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Deleted {$deleted} log(s) successfully.",
                'deleted_count' => $deleted,
            ]);
        }

        return redirect()->route('gl.request-logger.index')
            ->with('success', "Deleted {$deleted} log(s) successfully.");
    }

    /**
     * Check for new log entries.
     */
    public function checkNew(Request $request)
    {
        $latestId = $request->get('latest_id', 0);
        $latestTimestamp = $request->get('latest_timestamp');

        $query = RequestLog::query();

        if ($latestId > 0) {
            $query->where('id', '>', $latestId);
        } elseif ($latestTimestamp) {
            $query->where('created_at', '>', $latestTimestamp);
        }

        $newCount = $query->count();
        $latestLog = RequestLog::orderByDesc('id')->first();

        return response()->json([
            'has_new' => $newCount > 0,
            'new_count' => $newCount,
            'latest_id' => $latestLog ? $latestLog->id : 0,
            'latest_timestamp' => $latestLog ? $latestLog->created_at->toIso8601String() : null,
        ]);
    }
}
