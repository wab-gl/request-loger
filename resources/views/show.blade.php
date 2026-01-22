<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Log #{{ $log->id }}</title>
    <style>
        :root { color-scheme: dark; }
        body { font-family: "Inter", system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; margin: 24px; }
        h1 { margin-bottom: 12px; }
        .back-link { display: inline-block; margin-bottom: 20px; color: #60a5fa; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .detail-card { background: #111827; padding: 20px; border-radius: 8px; margin-bottom: 16px; }
        .detail-card h2 { margin-top: 0; margin-bottom: 16px; font-size: 18px; color: #e2e8f0; }
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px; margin-bottom: 20px; }
        .detail-item { background: #1f2937; padding: 12px; border-radius: 6px; }
        .detail-label { font-size: 12px; color: #94a3b8; margin-bottom: 4px; }
        .detail-value { font-size: 14px; color: #e2e8f0; word-break: break-word; }
        .badge { padding: 2px 8px; border-radius: 10px; font-size: 12px; text-transform: uppercase; display: inline-block; }
        .badge-get { background: #0ea5e9; color: #0b1221; }
        .badge-post { background: #22c55e; color: #0b1221; }
        .badge-put, .badge-patch { background: #f59e0b; color: #0b1221; }
        .badge-delete { background: #ef4444; color: #0b1221; }
        .badge-success { background: #22c55e; color: #0b1221; }
        .badge-error { background: #ef4444; color: #0b1221; }
        .badge-warning { background: #f59e0b; color: #0b1221; }
        .badge-slow { background: #dc2626; color: #fff; font-weight: 600; }
        pre { white-space: pre-wrap; word-break: break-word; font-size: 13px; background: #0b1221; padding: 16px; border-radius: 8px; border: 1px solid #1f2937; overflow-x: auto; }
        .json-key { color: #60a5fa; }
        .json-string { color: #34d399; }
        .json-number { color: #fbbf24; }
        .json-boolean { color: #a78bfa; }
        .json-null { color: #94a3b8; }
        .section-title { font-size: 16px; font-weight: 600; margin-bottom: 12px; color: #e2e8f0; }
        .empty-state { color: #94a3b8; font-style: italic; }
        .tabs { display: flex; gap: 8px; margin-bottom: 16px; border-bottom: 1px solid #1f2937; }
        .tab { padding: 12px 20px; background: transparent; border: none; color: #94a3b8; cursor: pointer; border-bottom: 2px solid transparent; margin-bottom: -1px; }
        .tab:hover { color: #e2e8f0; }
        .tab.active { color: #60a5fa; border-bottom-color: #60a5fa; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
    </style>
</head>
<body>
    <a href="{{ route('gl.request-logger.index') }}" class="back-link">← Back to Logs</a>
    <h1>Request Log #{{ $log->id }}</h1>

    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Method</div>
            <div class="detail-value">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <span class="badge badge-{{ strtolower($log->method) }}">{{ $log->method }}</span>
                    @if($log->duration_ms > $slow_threshold)
                        <span class="badge badge-slow">SLOW</span>
                    @endif
                </div>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Path</div>
            <div class="detail-value">{{ $log->path }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Status Code</div>
            <div class="detail-value">
                <span class="badge {{ $log->status_code >= 200 && $log->status_code < 300 ? 'badge-success' : ($log->status_code >= 400 ? 'badge-error' : 'badge-warning') }}">
                    {{ $log->status_code }}
                </span>
            </div>
        </div>
        <div class="detail-item">
            <div class="detail-label">IP Address</div>
            <div class="detail-value">{{ $log->ip ?? 'N/A' }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Duration</div>
            <div class="detail-value">{{ number_format($log->duration_ms, 2) }}ms</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Timestamp</div>
            <div class="detail-value">
                {{ $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : 'N/A' }}
                <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">
                    {{ $log->created_at ? $log->created_at->diffForHumans() : '' }}
                </div>
            </div>
        </div>
    </div>

    <div class="detail-card">
        <h2>Request & Response Data</h2>
        
        <div class="tabs">
            <button class="tab active" onclick="showTab('headers')">Headers</button>
            <button class="tab" onclick="showTab('body')">Request Body</button>
            <button class="tab" onclick="showTab('response')">Response Body</button>
        </div>

        <div id="headers" class="tab-content active">
            <div class="section-title">Request Headers</div>
            @if($log->headers && count($log->headers) > 0)
                <pre>{{ json_encode($log->headers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="empty-state">No headers available</div>
            @endif
        </div>

        <div id="body" class="tab-content">
            <div class="section-title">Request Body</div>
            @if($log->body && (is_array($log->body) ? count($log->body) > 0 : !empty($log->body)))
                <pre>{{ json_encode($log->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            @else
                <div class="empty-state">No request body</div>
            @endif
        </div>

        <div id="response" class="tab-content">
            <div class="section-title">Response Body</div>
            @if($log->response_body)
                @if(is_string($log->response_body) && $log->response_body === 'HTML response')
                    <div class="empty-state" style="color: #f59e0b; font-style: normal;">HTML response (HTML logging is disabled)</div>
                @elseif(is_array($log->response_body) && count($log->response_body) > 0)
                    <pre>{{ json_encode($log->response_body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                @elseif(is_string($log->response_body) && !empty($log->response_body))
                    <pre>{{ $log->response_body }}</pre>
                @else
                    <div class="empty-state">No response body</div>
                @endif
            @else
                <div class="empty-state">No response body</div>
            @endif
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
