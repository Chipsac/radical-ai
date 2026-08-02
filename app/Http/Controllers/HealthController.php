<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    /**
     * Liveness probe: is this process up?
     *
     * Deliberately checks nothing external. Cloud Run restarts an instance
     * that fails its liveness probe, and restarting will not fix a database
     * outage — it would just cycle every instance during an incident.
     */
    public function live(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'time' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness / diagnostics: can this instance actually serve traffic?
     *
     * Returns 503 when a dependency is down so a load balancer or uptime check
     * can act on it.
     */
    public function ready(): JsonResponse
    {
        $checks = [
            'database' => $this->check(fn () => DB::select('select 1')),
            'cache' => $this->check(function () {
                $key = 'health:'.uniqid();
                Cache::put($key, '1', 10);
                $hit = Cache::get($key) === '1';
                Cache::forget($key);

                if (! $hit) {
                    throw new \RuntimeException('cache write/read mismatch');
                }
            }),
            'storage' => $this->check(function () {
                $disk = Storage::disk(config('filesystems.default'));
                $path = 'health/'.uniqid().'.txt';
                $disk->put($path, 'ok');
                $ok = $disk->get($path) === 'ok';
                $disk->delete($path);

                if (! $ok) {
                    throw new \RuntimeException('storage write/read mismatch');
                }
            }),
        ];

        $healthy = collect($checks)->every(fn ($c) => $c['ok']);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'time' => now()->toIso8601String(),
            'checks' => $checks,
        ], $healthy ? 200 : 503);
    }

    /** Run a check, capturing latency and any failure message. */
    private function check(callable $probe): array
    {
        $started = microtime(true);

        try {
            $probe();

            return [
                'ok' => true,
                'ms' => round((microtime(true) - $started) * 1000, 1),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'ms' => round((microtime(true) - $started) * 1000, 1),
                // Class name only — an exception message can leak connection
                // strings or credentials, and this endpoint is unauthenticated.
                'error' => class_basename($e),
            ];
        }
    }
}
