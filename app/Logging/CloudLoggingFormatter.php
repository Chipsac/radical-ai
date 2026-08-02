<?php

namespace App\Logging;

use App\Support\TenantContext;
use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

/**
 * Emits log lines in the shape Google Cloud Logging understands, so severity,
 * tenant and user are filterable fields rather than text buried in a message.
 *
 * Cloud Run reads structured JSON from stdout/stderr automatically — no agent
 * and no extra cost.
 */
class CloudLoggingFormatter extends JsonFormatter
{
    /** Monolog level names mapped to Cloud Logging severities. */
    private const SEVERITY = [
        'DEBUG' => 'DEBUG',
        'INFO' => 'INFO',
        'NOTICE' => 'NOTICE',
        'WARNING' => 'WARNING',
        'ERROR' => 'ERROR',
        'CRITICAL' => 'CRITICAL',
        'ALERT' => 'ALERT',
        'EMERGENCY' => 'EMERGENCY',
    ];

    public function format(LogRecord $record): string
    {
        $payload = [
            'severity' => self::SEVERITY[$record->level->getName()] ?? 'DEFAULT',
            'message' => $record->message,
            'time' => $record->datetime->format(\DateTimeInterface::RFC3339_EXTENDED),
            'channel' => $record->channel,
        ];

        if ($context = $this->enrichedContext($record)) {
            $payload['context'] = $context;
        }

        return $this->toJson($payload, true).($this->appendNewline ? "\n" : '');
    }

    /**
     * Attach who and which tenant, so a production error can be traced to an
     * organisation without correlating by hand.
     */
    private function enrichedContext(LogRecord $record): array
    {
        $context = $record->context;

        try {
            if (app()->bound(TenantContext::class)) {
                $tenantId = app(TenantContext::class)->id();
                if ($tenantId !== null) {
                    $context['tenant_id'] = $tenantId;
                }
            }

            if (auth()->hasUser()) {
                $context['user_id'] = auth()->id();
            }

            // Cloud Trace propagates this header; including it links a log
            // line to the request it came from.
            if ($trace = request()?->header('X-Cloud-Trace-Context')) {
                $context['trace_id'] = explode('/', $trace)[0];
            }
        } catch (\Throwable) {
            // Logging must never be the thing that breaks a request.
        }

        return array_filter($context, fn ($v) => $v !== null);
    }
}
