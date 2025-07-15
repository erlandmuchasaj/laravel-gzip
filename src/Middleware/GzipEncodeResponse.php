<?php

namespace ErlandMuchasaj\LaravelGzip\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class GzipEncodeResponse
{
    private array $config;

    public function __construct()
    {
        $this->config = config('laravel-gzip', []);
    }

        /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // @return Response|RedirectResponse|JsonResponse|ResponseAlias|BinaryFileResponse|StreamedResponse
        $response = $next($request);

        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if (empty($content)) {
            return $response;
        }

        $etag = sha1($content);
        if ($request->headers->get('If-None-Match') === $etag) {
            return response()->noContent(304)->withHeaders([
                'ETag' => $etag,
                'Content-Encoding' => 'gzip',
                'Vary' => 'Accept-Encoding',
            ]);
        }

        $content = $this->prepareContentForCompression($content, $response);

        // Level 5 compression is a perfect compromise between size and CPU
        $compressed = gzencode($content, $this->gzipLevel());
        if ($compressed === false) {
            return $response;
        }

        // Check if the compression ratio is worthwhile
        if (!$this->compressionWorthwhile($content, $compressed)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('ETag', $etag);
        $this->setCompressionHeaders($response, strlen($compressed));

        if ($this->shouldLog()) {
            $this->logCompressionStats(strlen($content), strlen($compressed));
        }

        return $response;
    }

    private function setCompressionHeaders(Response $response, int $length): void
    {
        $response->headers->add([
            'Content-Encoding' => 'gzip',
            'Content-Length' => $length,
        ]);

        // Handle Vary header properly
        $vary = $response->headers->get('Vary', '');
        $variations = array_filter(array_map('trim', explode(',', $vary)));

        if (! in_array('Accept-Encoding', $variations)) {
            $variations[] = 'Accept-Encoding';
            $response->headers->set('Vary', implode(', ', $variations));
        }
    }

    private function shouldCompress(Request $request, $response): bool
    {
        // check if the package is enabled
        if (! $this->shouldGzipResponse()) {
            return false;
        }

        // Environment checks
        if (app()->isLocal() || app()->runningUnitTests()) {
            return false;
        }

        // if debug is enabled, we do not gzip the response
        if ($this->gzipDebugEnabled()) {
            return false;
        }

        // Response type checks for a steamed file we do not compress
        if ($response instanceof BinaryFileResponse ||
            $response instanceof StreamedResponse ||
            !$response instanceof Response
        ) {
            return false;
        }

        // Client capability checks
        if (
            !in_array('gzip', $request->getEncodings()) ||
            !function_exists('gzencode')
        ) {
            return false;
        }

        // Existing encoding check
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }

        // if there is a minimum content length, we do not compress the response
        if (!$this->hasMinimumContentLength($response)) {
            return false;
        }

        // Content type checks
        if (! $this->shouldCompressContentType($response)) {
            return false;
        }

        return !$response->isRedirection();
    }

    /**
     * Determine if the content type should be compressed
     */
    private function shouldCompressContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        $nonCompressibleTypes = $this->config['excluded_mime_types'] ?? [];

        if (Str::startsWith($contentType, $nonCompressibleTypes)) {
            return false;
        }

        // Only compress known compressible types
        $compressibleTypes = $this->config['compressible_mime_types'] ?? $this->getDefaultCompressibleTypes();
        if (Str::startsWith($contentType, $compressibleTypes)) {
            return true;
        }

        return false;
    }

    private function getDefaultCompressibleTypes(): array
    {
        return [
            'text/',
            'application/json',
            'application/javascript',
            'application/xml',
            'application/x-javascript',
            'application/rss+xml',
            'application/atom+xml',
            'image/svg+xml',
        ];
    }

    /**
     * Prepare content for compression (especially important for CSS)
     */
    private function prepareContentForCompression(string $content, Response $response): string
    {
        $contentType = $response->headers->get('Content-Type', '');

        if (Str::startsWith($contentType, 'text/css')) {
            // Ensure UTF-8 encoding for CSS
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            }

            // Ensure the charset is set
            if (!Str::contains($contentType, 'charset')) {
                $response->headers->set('Content-Type', 'text/css; charset=UTF-8');
            }
        }

        return $content;
    }

    private function compressionWorthwhile(string $original, string $compressed): bool
    {
        $ratio = strlen($compressed) / strlen($original);
        $minRatio = $this->config['minimum_compression_ratio'] ?? 0.95;

        return $ratio < $minRatio;
    }

    /**
     * Log compression statistics for debugging
     */
    private function logCompressionStats(int $originalSize, int $compressedSize): void
    {
        $ratio = $originalSize > 0 ? round(($compressedSize / $originalSize) * 100, 2) : 0;
        $saved = $originalSize - $compressedSize;

        Log::debug("Gzip compression: $originalSize bytes → $compressedSize bytes ($ratio%), saved $saved bytes");
    }

    protected function hasMinimumContentLength(Response $response): bool
    {
        $content = $response->getContent();
        return $content !== false && strlen($content) >= $this->minimumContentLength();
    }

    /**
     * Decides if we should gzip the response or not.
     *
     * @return bool
     */
    private function shouldGzipResponse(): bool
    {
        return (bool) ($this->config['enabled'] ?? true);
    }

    protected function minimumContentLength(): int
    {
        return (int) ($this->config['minimum_content_length'] ?? 1024);
    }

    /**
     * Get the gzip encoding level.
     * @return int
     */
    private function gzipLevel(): int
    {
        $level = (int) ($this->config['level'] ?? 5);
        return max(1, min(9, $level));
    }

    private function shouldLog(): bool
    {
        return (bool) $this->config['log'];
    }

    /**
     * Get the gzip debug enabled.
     * If the debugger is enabled, we do not gzip the response.
     *
     * @return bool
     */
    private function gzipDebugEnabled(): bool
    {
        return (bool) $this->config['debug'];
    }

}
