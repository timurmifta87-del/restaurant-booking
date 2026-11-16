<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/** Пишет в журнал каждый запрос к API. Тело запроса и заголовки не пишутся: в них пароли и токены. */
class LogApiRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $started = microtime(true);

        $response = $next($request);

        $status = $response->getStatusCode();
        $context = [
            'method' => $request->method(),
            'url' => $request->path(),
            'query' => $request->query(),
            'user_id' => $request->user()?->id,
            'status' => $status,
            'duration_ms' => round((microtime(true) - $started) * 1000, 1),
            'ip' => $request->ip(),
        ];

        match (true) {
            $status >= 500 => Log::error('API request', $context),
            $status >= 400 => Log::warning('API request', $context),
            default => Log::info('API request', $context),
        };

        return $response;
    }
}
