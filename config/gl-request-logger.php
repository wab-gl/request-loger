<?php

return [
    'enabled' => env('GL_REQUEST_LOGGER_ENABLED', env('REQUEST_LOGGER_ENABLED', true)),

    'driver' => env('GL_REQUEST_LOGGER_DRIVER', env('REQUEST_LOGGER_DRIVER', 'database')), // 'database' or 'file'

    'table' => 'gl_request_logs',

    'file_channel' => env('GL_REQUEST_LOGGER_CHANNEL', env('REQUEST_LOGGER_CHANNEL', env('LOG_CHANNEL', 'stack'))),

    'masked_keys' => [
        'password',
        'password_confirmation',
        'authorization',
        'token',
        'api_key',
        'apikey',
        'secret',
        'session',
        'cookie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Routes (Path/URI Patterns)
    |--------------------------------------------------------------------------
    |
    | Patterns to exclude from logging. Uses Laravel's Str::is() matching.
    | Supports wildcards: 'admin/*', 'api/users*', etc.
    |
    */
    'ignored_routes' => [
        'gl/request-logs*',
        'gl/request-logs-check-new',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored URLs (Full URL Patterns)
    |--------------------------------------------------------------------------
    |
    | Full URL patterns to exclude from logging. Supports wildcards and regex.
    | Examples: 'https://example.com/api/*' or regex patterns.
    |
    */
    'ignored_urls' => [
        // 'https://example.com/webhook*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignored Paths (Regex Patterns)
    |--------------------------------------------------------------------------
    |
    | Regular expression patterns for path/URI matching. Must be valid regex.
    | Examples: '/^\/api\/v\d+\/.*$/', '/^\/admin\/.*$/'
    |
    */
    'ignored_paths_regex' => [
        // '/^\/api\/v\d+\/health$/',
    ],

    /*
    |--------------------------------------------------------------------------
    | Slow Request Threshold
    |--------------------------------------------------------------------------
    |
    | Requests taking longer than this duration (in milliseconds) will be
    | marked as "slow" in the log viewer.
    |
    */
    'slow_request_threshold_ms' => env('GL_REQUEST_LOGGER_SLOW_THRESHOLD', env('REQUEST_LOGGER_SLOW_THRESHOLD', 1000)),

    /*
    |--------------------------------------------------------------------------
    | Log HTML Responses
    |--------------------------------------------------------------------------
    |
    | If set to false, HTML responses will be replaced with "HTML response"
    | text instead of logging the actual HTML content. This helps reduce
    | database size and improve performance.
    |
    */
    'log_html_responses' => env('GL_REQUEST_LOGGER_LOG_HTML', env('REQUEST_LOGGER_LOG_HTML', false)),

    /*
    |--------------------------------------------------------------------------
    | Pagination Per Page
    |--------------------------------------------------------------------------
    |
    | Number of log entries to display per page in the log viewer UI.
    | Adjust this value based on your needs and server performance.
    |
    */
    'per_page' => env('GL_REQUEST_LOGGER_PER_PAGE', env('REQUEST_LOGGER_PER_PAGE', 50)),
];

