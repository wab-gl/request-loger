<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Logs</title>
    <style>
        :root { color-scheme: dark; }
        body { font-family: "Inter", system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; margin: 24px; }
        h1 { margin-bottom: 12px; }
        .search-form { background: #111827; padding: 16px; border-radius: 8px; margin-bottom: 20px; }
        .search-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto; gap: 12px; align-items: end; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group label { font-size: 12px; color: #94a3b8; }
        input, select { background: #1f2937; border: 1px solid #374151; color: #e2e8f0; padding: 8px 12px; border-radius: 6px; font-size: 14px; }
        input:focus, select:focus { outline: none; border-color: #1d4ed8; }
        .btn { background: #1d4ed8; color: #e2e8f0; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-size: 14px; }
        .btn:hover { background: #2563eb; }
        .btn-secondary { background: #374151; }
        .btn-secondary:hover { background: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px 12px; border-bottom: 1px solid #1f2937; text-align: left; vertical-align: top; }
        th { background: #111827; position: sticky; top: 0; }
        tr:nth-child(even) { background: #0b1221; }
        tr:hover { background: #1a2332; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; text-transform: uppercase; display: inline-block; }
        .badge-get { background: #0ea5e9; color: #0b1221; }
        .badge-post { background: #22c55e; color: #0b1221; }
        .badge-put, .badge-patch { background: #f59e0b; color: #0b1221; }
        .badge-delete { background: #ef4444; color: #0b1221; }
        button { background: #1d4ed8; color: #e2e8f0; border: none; padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 12px; }
        button:hover { background: #2563eb; }
        .link { color: #60a5fa; text-decoration: none; }
        .link:hover { text-decoration: underline; }
        pre { margin: 8px 0 0; white-space: pre-wrap; word-break: break-word; font-size: 13px; background: #111827; padding: 10px; border-radius: 8px; }
        .meta { color: #94a3b8; font-size: 12px; }
        .payload { display: none; }
        .pagination { margin-top: 20px; display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 12px; border: 1px solid #1f2937; border-radius: 6px; text-decoration: none; color: inherit; display: inline-block; }
        .pagination .active { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
        .pagination a:hover:not(.active) { background: #1f2937; }
        .pagination .disabled { opacity: 0.5; cursor: not-allowed; }
        .stats { display: flex; gap: 16px; margin-bottom: 16px; }
        .stat { background: #111827; padding: 12px; border-radius: 6px; }
        .stat-label { font-size: 12px; color: #94a3b8; }
        .stat-value { font-size: 18px; font-weight: 600; margin-top: 4px; }
        .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .alert { padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; }
        .alert-success { background: #065f46; color: #6ee7b7; border: 1px solid #10b981; }
        .badge-slow { background: #dc2626; color: #fff; font-weight: 600; }
        .new-entries-notification { position: fixed; top: 20px; right: 20px; background: #1d4ed8; color: #fff; padding: 12px 20px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3); z-index: 1000; display: none; }
        .new-entries-notification.show { display: block; animation: slideIn 0.3s ease-out; }
        .new-entries-notification a { color: #fff; text-decoration: underline; font-weight: 600; }
        .new-entries-notification a:hover { text-decoration: none; }
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="header-actions">
        <h1 style="margin: 0;">Request Logs</h1>
        @if($logs->total() > 0)
            <form method="POST" action="{{ route('gl.request-logger.destroy') }}" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete all {{ $logs->total() }} log entries? This action cannot be undone.');">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Clear All Records</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div id="newEntriesNotification" class="new-entries-notification">
        <div style="margin-bottom: 8px;">New log entries available!</div>
        <a href="{{ route('gl.request-logger.index', request()->query()) }}" id="loadNewEntriesLink">Load new entries</a>
    </div>
    
    <form method="GET" action="{{ route('gl.request-logger.index') }}" class="search-form">
        <div class="search-row">
            <div class="form-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="{{ $search }}" placeholder="Path, method, IP, status...">
            </div>
            <div class="form-group">
                <label for="method">Method</label>
                <select id="method" name="method">
                    <option value="">All</option>
                    <option value="GET" {{ $method === 'GET' ? 'selected' : '' }}>GET</option>
                    <option value="POST" {{ $method === 'POST' ? 'selected' : '' }}>POST</option>
                    <option value="PUT" {{ $method === 'PUT' ? 'selected' : '' }}>PUT</option>
                    <option value="PATCH" {{ $method === 'PATCH' ? 'selected' : '' }}>PATCH</option>
                    <option value="DELETE" {{ $method === 'DELETE' ? 'selected' : '' }}>DELETE</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status_code">Status</label>
                <input type="number" id="status_code" name="status_code" value="{{ $status_code }}" placeholder="200, 404...">
            </div>
            <div class="form-group">
                <label for="date_from">From</label>
                <input type="date" id="date_from" name="date_from" value="{{ $date_from }}">
            </div>
            <div class="form-group">
                <label for="date_to">To</label>
                <input type="date" id="date_to" name="date_to" value="{{ $date_to }}">
            </div>
            <div class="form-group">
                <button type="submit" class="btn">Search</button>
                @if($search || $method || $status_code || $date_from || $date_to)
                    <a href="{{ route('gl.request-logger.index') }}" class="btn btn-secondary" style="margin-top: 8px;">Clear</a>
                @endif
            </div>
        </div>
    </form>

    <div class="stats">
        <div class="stat">
            <div class="stat-label">Total Logs</div>
            <div class="stat-value">{{ $logs->total() }}</div>
        </div>
        <div class="stat">
            <div class="stat-label">Showing</div>
            <div class="stat-value">{{ $logs->firstItem() ?? 0 }} - {{ $logs->lastItem() ?? 0 }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>When</th>
                <th>Request</th>
                <th>IP</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td>
                        <div>{{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A' }}</div>
                        <div class="meta">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
                    </td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="badge badge-{{ strtolower($log->method) }}">{{ $log->method }}</span>
                            @if($log->duration_ms > $slow_threshold)
                                <span class="badge badge-slow">SLOW</span>
                            @endif
                        </div>
                        <div style="margin-top: 4px;">
                            <a href="{{ route('gl.request-logger.show', $log->id) }}" class="link">{{ $log->path }}</a>
                        </div>
                        <div class="meta">Status: {{ $log->status_code }} | Duration: {{ number_format($log->duration_ms, 2) }}ms</div>
                    </td>
                    <td>{{ $log->ip ?? 'N/A' }}</td>
                    <td>
                        <a href="{{ route('gl.request-logger.show', $log->id) }}" class="btn" style="text-decoration: none; display: inline-block;">View Details</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No logs found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($logs->hasPages())
        <div class="pagination">
            {{-- Previous Page Link --}}
            @if ($logs->onFirstPage())
                <span class="disabled">&laquo; Previous</span>
            @else
                <a href="{{ $logs->previousPageUrl() }}" rel="prev">&laquo; Previous</a>
            @endif

            {{-- Pagination Elements --}}
            @php
                $currentPage = $logs->currentPage();
                $lastPage = $logs->lastPage();
                $startPage = max(1, $currentPage - 2);
                $endPage = min($lastPage, $currentPage + 2);
            @endphp

            @if($startPage > 1)
                <a href="{{ $logs->url(1) }}">1</a>
                @if($startPage > 2)
                    <span>...</span>
                @endif
            @endif

            @for($page = $startPage; $page <= $endPage; $page++)
                @if ($page == $currentPage)
                    <span class="active">{{ $page }}</span>
                @else
                    <a href="{{ $logs->url($page) }}">{{ $page }}</a>
                @endif
            @endfor

            @if($endPage < $lastPage)
                @if($endPage < $lastPage - 1)
                    <span>...</span>
                @endif
                <a href="{{ $logs->url($lastPage) }}">{{ $lastPage }}</a>
            @endif

            {{-- Next Page Link --}}
            @if ($logs->hasMorePages())
                <a href="{{ $logs->nextPageUrl() }}" rel="next">Next &raquo;</a>
            @else
                <span class="disabled">Next &raquo;</span>
            @endif
        </div>
    @endif

    <script>
        (function() {
            const latestLogId = {{ $latest_log_id }};
            const checkUrl = '{{ route("gl.request-logger.check-new") }}';
            const notification = document.getElementById('newEntriesNotification');
            const loadLink = document.getElementById('loadNewEntriesLink');
            let pollingInterval;
            let isPolling = false;

            function checkForNewEntries() {
                if (isPolling) return;
                isPolling = true;

                fetch(`${checkUrl}?latest_id=${latestLogId}&_=${Date.now()}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.has_new && data.new_count > 0) {
                            notification.classList.add('show');
                            const currentUrl = new URL(window.location.href);
                            loadLink.href = currentUrl.toString();
                        } else {
                            notification.classList.remove('show');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking for new entries:', error);
                    })
                    .finally(() => {
                        isPolling = false;
                    });
            }

            // Start polling every 5 seconds
            if (latestLogId > 0) {
                pollingInterval = setInterval(checkForNewEntries, 5000);
                
                // Also check immediately after a short delay
                setTimeout(checkForNewEntries, 2000);
            }

            // Stop polling when page is hidden
            document.addEventListener('visibilitychange', function() {
                if (document.hidden) {
                    if (pollingInterval) {
                        clearInterval(pollingInterval);
                    }
                } else {
                    if (latestLogId > 0 && !pollingInterval) {
                        pollingInterval = setInterval(checkForNewEntries, 5000);
                        checkForNewEntries();
                    }
                }
            });

            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                }
            });
        })();
    </script>

</body>
</html>
