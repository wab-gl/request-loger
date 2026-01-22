<?php

namespace GreeLogix\RequestLogger\Http\Middleware;

use GreeLogix\RequestLogger\Models\RequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (!config('gl-request-logger.enabled', true)) {
            return $next($request);
        }

        if ($this->isIgnoredRoute($request)) {
            return $next($request);
        }

        $start = microtime(true);

        /** @var Response $response */
        $response = $next($request);
        $duration = (microtime(true) - $start) * 1000;

        $this->storeLog($request, $response, $duration);

        return $response;
    }

    /**
     * Determine if a route should be skipped.
     */
    protected function isIgnoredRoute(Request $request): bool
    {
        // Check path patterns (URI/endpoint patterns)
        $pathPatterns = (array) config('gl-request-logger.ignored_routes', []);
        $uri = $request->path();

        foreach ($pathPatterns as $pattern) {
            if (Str::is($pattern, $uri)) {
                return true;
            }
        }

        // Check full URL patterns
        $urlPatterns = (array) config('gl-request-logger.ignored_urls', []);
        $fullUrl = $request->fullUrl();

        foreach ($urlPatterns as $pattern) {
            if (Str::is($pattern, $fullUrl) || $this->matchesRegex($pattern, $fullUrl)) {
                return true;
            }
        }

        // Check regex patterns for paths
        $regexPatterns = (array) config('gl-request-logger.ignored_paths_regex', []);
        foreach ($regexPatterns as $pattern) {
            if ($this->matchesRegex($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a string matches a regex pattern.
     */
    protected function matchesRegex(string $pattern, string $subject): bool
    {
        // If pattern doesn't look like regex, treat as simple string match
        if (!str_starts_with($pattern, '/') && !str_starts_with($pattern, '#')) {
            return false;
        }

        try {
            return (bool) preg_match($pattern, $subject);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Store the request log in the configured driver.
     */
    protected function storeLog(Request $request, Response $response, float $durationMs): void
    {
        $driver = config('gl-request-logger.driver', 'database');
        $payload = $this->buildPayload($request, $response, $durationMs);

        if ($driver === 'database' && $this->canUseDatabase()) {
            RequestLog::create($payload);
            return;
        }

        $channel = config('gl-request-logger.file_channel', config('logging.default'));
        Log::channel($channel)->info('Request log', $payload);
    }

    /**
     * Build the log payload with masked sensitive data.
     */
    protected function buildPayload(Request $request, Response $response, float $durationMs): array
    {
        $maskedKeys = array_map(fn ($key) => Str::lower($key), (array) config('gl-request-logger.masked_keys', []));
        $input = $this->maskData($request->all(), $maskedKeys);
        $headers = $this->maskData($request->headers->all(), $maskedKeys);

        // Try multiple ways to get the authenticated user
        $userId = null;
        if ($request->user()) {
            $userId = $request->user()->id;
        } elseif (auth()->check()) {
            $userId = auth()->id();
        } elseif (auth('web')->check()) {
            $userId = auth('web')->id();
        } elseif (auth('sanctum')->check()) {
            $userId = auth('sanctum')->id();
        }

        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'ip' => $request->ip(),
            'user_id' => $userId,
            'headers' => $headers,
            'body' => $input,
            'response_body' => $this->processResponseBody($response, $maskedKeys),
            'duration_ms' => $durationMs,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Mask sensitive data recursively.
     */
    protected function maskData($data, array $maskedKeys)
    {
        if (is_object($data)) {
            $data = (array) $data;
        }

        if (!is_array($data)) {
            return $data;
        }

        $result = [];
        foreach ($data as $key => $value) {
            $normalizedKey = Str::lower((string) $key);
            if (in_array($normalizedKey, $maskedKeys, true) || $this->matchesMaskedPattern($normalizedKey, $maskedKeys)) {
                $result[$key] = '***';
                continue;
            }

            $result[$key] = $this->maskData($value, $maskedKeys);
        }

        return $result;
    }

    /**
     * Detect masked patterns (simple contains match for known tokens).
     */
    protected function matchesMaskedPattern(string $key, array $maskedKeys): bool
    {
        foreach ($maskedKeys as $mask) {
            if (Str::contains($key, $mask)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the database is available and the table exists.
     */
    protected function canUseDatabase(): bool
    {
        $table = config('gl-request-logger.table', 'gl_request_logs');

        try {
            return DB::connection()->getSchemaBuilder()->hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Process response body - handle JSON, HTML, and other content types.
     */
    protected function processResponseBody(Response $response, array $maskedKeys)
    {
        $content = $response->getContent();
        $contentType = $response->headers->get('Content-Type', '');

        // Check if HTML logging is disabled
        $logHtml = config('gl-request-logger.log_html_responses', true);
        
        // Detect HTML responses
        $isHtml = Str::contains(strtolower($contentType), 'text/html') 
            || (is_string($content) && $this->isHtmlContent($content));

        if ($isHtml && !$logHtml) {
            return 'HTML response';
        }

        // Try to decode JSON
        $decoded = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $this->maskData($decoded, $maskedKeys);
        }

        // Return content as-is (or masked if it's an array/object)
        return $this->maskData($content, $maskedKeys);
    }

    /**
     * Check if content is HTML.
     */
    protected function isHtmlContent(string $content): bool
    {
        // Check for common HTML tags at the start (after whitespace)
        $trimmed = trim($content);
        return Str::startsWith($trimmed, ['<!DOCTYPE', '<html', '<HTML', '<body', '<BODY', '<div', '<DIV']);
    }

    /**
     * Decode JSON response bodies when possible.
     * @deprecated Use processResponseBody instead
     */
    protected function decodeJsonResponse(Response $response)
    {
        $content = $response->getContent();
        $decoded = json_decode($content, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $content;
    }
}
