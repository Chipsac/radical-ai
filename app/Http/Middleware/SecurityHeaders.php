<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defence-in-depth HTTP response headers.
 *
 * These do not replace server-side authorisation — they reduce the damage a
 * browser can be talked into doing: framing the app for clickjacking, running
 * injected script, or leaking URLs through the referer header.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            // Never render inside someone else's frame.
            'X-Frame-Options' => 'DENY',
            // Trust our declared content types, don't sniff.
            'X-Content-Type-Options' => 'nosniff',
            // Send the origin cross-site, the full URL same-site. Stops
            // invitation tokens leaking in referers to third parties.
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            // We ask for none of these.
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'X-Permitted-Cross-Domain-Policies' => 'none',
        ];

        if (app()->environment('production')) {
            // Only meaningful over HTTPS, and hard to undo, so production only.
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        $headers['Content-Security-Policy'] = $this->contentSecurityPolicy();

        foreach ($headers as $name => $value) {
            // Never clobber a header a controller set deliberately.
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Firebase Hosting's CDN sits in front of Cloud Run, so a cacheable
        // response carrying one tenant's data could in principle be replayed to
        // another. Laravel already marks most authenticated responses private,
        // but on a multi-tenant app that is not something to leave to a default.
        //
        // Set after the loop and unconditionally: the loop above deliberately
        // skips headers that already exist, and Laravel always sets a
        // Cache-Control, so adding it there would have silently done nothing.
        if ($request->user()) {
            $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        }

        $response->headers->remove('X-Powered-By');

        return $response;
    }

    /**
     * Vite emits a small inline module loader and Alpine uses inline handlers,
     * so 'unsafe-inline' is required for scripts and styles today.
     *
     * 'unsafe-eval' is required too, and not optionally: Alpine 3 compiles every
     * directive — x-data, x-show, @click, :class — with `new Function()`. Without
     * it the browser refuses each one, Alpine silently yields an empty data
     * object, and every interactive element on the site fails open. That is how
     * the mobile menu came to sit permanently expanded in production while
     * working locally, because only this environment branch omitted it.
     *
     * Note what this does and does not cost. 'unsafe-inline' is already present,
     * and it is the directive that does most of the work of stopping injected
     * script; with it set, CSP is already not the defence here. Adding
     * 'unsafe-eval' widens that gap only slightly.
     *
     * The real fix is @alpinejs/csp, whose build evaluates no strings, but it
     * requires every inline expression to move into registered Alpine.data()
     * components. Worth doing deliberately — not mid-incident.
     */
    private function contentSecurityPolicy(): string
    {
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: blob:",
            "connect-src 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "object-src 'none'",
        ];

        if (app()->environment('production')) {
            $directives[] = 'upgrade-insecure-requests';
        } else {
            // Vite's dev server and its HMR websocket.
            $directives[1] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' http://localhost:* http://127.0.0.1:*";
            $directives[5] = "connect-src 'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*";
        }

        return implode('; ', $directives);
    }
}
