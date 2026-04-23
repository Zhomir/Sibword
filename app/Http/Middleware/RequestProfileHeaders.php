<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RequestProfileHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $enabled = app()->isLocal() && (
            $request->boolean('profile')
            || $request->header('X-Debug-Profile') === '1'
        );

        if (!$enabled) {
            return $next($request);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $startNs = hrtime(true);
        $startMem = memory_get_usage(true);

        /** @var Response $response */
        $response = $next($request);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $sqlTimeMs = array_sum(array_map(
            static fn (array $query): float => (float) ($query['time'] ?? 0.0),
            $queries
        ));

        $totalMs = (hrtime(true) - $startNs) / 1000000;
        $memoryMb = (memory_get_usage(true) - $startMem) / 1024 / 1024;

        $response->headers->set('X-Perf-Queries', (string) count($queries));
        $response->headers->set('X-Perf-SQL-Time-Ms', number_format($sqlTimeMs, 2, '.', ''));
        $response->headers->set('X-Perf-Total-Time-Ms', number_format($totalMs, 2, '.', ''));
        $response->headers->set('X-Perf-Memory-Delta-Mb', number_format($memoryMb, 2, '.', ''));

        return $response;
    }
}
