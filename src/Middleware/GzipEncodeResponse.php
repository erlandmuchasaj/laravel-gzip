<?php

namespace ErlandMuchasaj\LaravelGzip\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GzipEncodeResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // @return Response|RedirectResponse|JsonResponse|ResponseAlias|BinaryFileResponse|StreamedResponse
        $response = $next($request);

        if (! $this->shouldGzipResponse()) {
            return $response;
        }

        if (! app()->isProduction()) {
            return $response;
        }

        if ($this->gzipDebugEnabled()) {
            return $response;
        }

        if ($response instanceof BinaryFileResponse || $response instanceof StreamedResponse) {
            return $response;
        }

        if (! $response instanceof Response) {
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
     *
     * @return bool
     */
    private function shouldGzipResponse(): bool
    {
        return config()->has('laravel-gzip.enabled')
            ? config('laravel-gzip.enabled')
            : true;
    }

    /**
     * Get the gzip encoding level.
     * @return int
     */
    private function gzipLevel(): int
    {
        return config()->has('laravel-gzip.level')
            ? config('laravel-gzip.level')
            : 5;
    }

    /**
     * Get the gzip debug enabled.
     * If debugbar is enabled we do not gzip the response.
     *
     * @return bool
     */
    private function gzipDebugEnabled(): bool
    {
        return config()->has('laravel-gzip.debug')
            ? config('laravel-gzip.debug')
            : false;
    }
}
