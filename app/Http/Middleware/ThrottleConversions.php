<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * SECURITY: Rate Limiting Middleware for Conversion Endpoints
 *
 * Prevents abuse and DoS attacks by limiting the number of conversion requests
 * per IP address within a time window.
 */
class ThrottleConversions
{
    /**
     * Maximum number of conversion requests per minute
     */
    private const MAX_ATTEMPTS_PER_MINUTE = 10;

    /**
     * Maximum number of conversion requests per hour
     */
    private const MAX_ATTEMPTS_PER_HOUR = 50;

    /**
     * Handle an incoming request
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ip = $request->ip();

        // Rate limit key based on IP and endpoint
        $keyMinute = 'conversions:' . $ip . ':minute';
        $keyHour = 'conversions:' . $ip . ':hour';

        // Check per-minute limit
        if (RateLimiter::tooManyAttempts($keyMinute, self::MAX_ATTEMPTS_PER_MINUTE)) {
            $seconds = RateLimiter::availableIn($keyMinute);

            return response()->json([
                'success' => false,
                'error' => 'Too many conversion requests. Please try again in ' . $seconds . ' seconds.',
                'retry_after' => $seconds
            ], 429);
        }

        // Check per-hour limit
        if (RateLimiter::tooManyAttempts($keyHour, self::MAX_ATTEMPTS_PER_HOUR)) {
            $seconds = RateLimiter::availableIn($keyHour);
            $minutes = ceil($seconds / 60);

            return response()->json([
                'success' => false,
                'error' => 'Hourly conversion limit exceeded. Please try again in ' . $minutes . ' minutes.',
                'retry_after' => $seconds
            ], 429);
        }

        // Increment rate limit counters
        RateLimiter::hit($keyMinute, 60); // 1 minute decay
        RateLimiter::hit($keyHour, 3600); // 1 hour decay

        return $next($request);
    }
}
