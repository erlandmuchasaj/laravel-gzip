<?php

namespace ErlandMuchasaj\LaravelGzip\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GzipEncodeResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // @return Response|RedirectResponse|JsonResponse|ResponseAlias
        $response = $next($request);
        
        if (! $this->shouldGzipResponse()) {
            return $response;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        if (in_array('gzip', $request->getEncodings()) && function_exists('gzencode')) {

            // 5 is a perfect compromise between size and CPU
            $compressed = gzencode((string) $response->getContent(), $this->gzipLevel());

            if ($compressed) {
                // Get response length
                $response->setContent($compressed);

                $response->headers->add([
                    'Content-Encoding' => 'gzip',
                    'Vary' => 'Accept-Encoding',
                    'Content-Length' => strlen($compressed),
                ]);
            }
        }

        return $response;
    }

    /**
     * Decides if we should gzip the response or not.
     */
    private function shouldGzipResponse(): bool
    {
        return config()->has('laravel-gzip.enabled')
            ? config('laravel-gzip.enabled')
            : true;
    }

    /**
     * Get the gzip encoding level.
     */
    private function gzipLevel(): int
    {
        return config()->has('laravel-gzip.level')
            ? config('laravel-gzip.level')
            : 5;
    }
}
