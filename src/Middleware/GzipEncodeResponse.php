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
    private ?bool $hasBrotli = null;
    private ?bool $hasGzip = null;

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

        // dump($request, $response);
        // dump([
        //     'shouldGzipResponse' => $this->shouldGzipResponse(),
        //     'skipEnvironment' => $this->skipEnvironment(),
        //     'gzipDebugEnabled' => $this->gzipDebugEnabled(),
        //     'isCompressibleResponseType' => $this->isCompressibleResponseType($response),
        //     'clientSupportsCompression' => $this->clientSupportsCompression($request),
        //     '$response instanceof Response' => ($response instanceof Response),
        //     'Has Content-Encoding' => $response->headers->has('Content-Encoding'),
        //     'Has Range' => $request->headers->has('Range'),
        //     'headers' => $request->headers,
        //     'hasMinimumContentLength' => $this->hasMinimumContentLength($response),
        //     'shouldCompressContentType' => $this->shouldCompressContentType($response),
        //     'isPathExcluded' => $this->isPathExcluded($request),
        //     'isRedirection' => $response->isRedirection(),
        //     'shouldCompress' => $this->shouldCompress($request, $response),
        // ]);

        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }

        $content = $response->getContent();
        if (empty($content)) {
            return $response;
        }



        // Determine best encoding method
        $encoding = $this->getBestEncoding($request);
        if ($encoding === null) {
            return $response;
        }


        // Handle ETag for caching
        $etag = $this->generateETag($content, $encoding);

        if ($this->handleETagMatch($request, $etag)) {
            return response()
                ->noContent(Response::HTTP_NOT_MODIFIED)
                ->withHeaders([
                    'ETag' => $etag,
                    'Content-Encoding' => 'gzip',
                    'Vary' => 'Accept-Encoding',
                ]);
        }

        $content = $this->prepareContentForCompression($content, $response);
        // Attempt compression
        // Level 6 compression is a perfect compromise between size and CPU
        $compressed = $this->compressContent($content, $encoding);
        if ($compressed === false) {
            if ($this->shouldLog()) {
                Log::warning('Gzip compression failed for response');
            }
            return $response;
        }

        // Check if the compression ratio is worthwhile
        if (!$this->compressionWorthwhile($content, $compressed)) {
            if ($this->shouldLog()) {
                Log::debug('Gzip compression not worthwhile, serving uncompressed');
            }
            return $response;
        }


        $response->setContent($compressed);
        $response->headers->set('ETag', $etag);
        $this->setCompressionHeaders($response, strlen($compressed), $encoding);

        if ($this->shouldLog()) {
            $this->logCompressionStats(strlen($content), strlen($compressed));
        }

        return $response;
    }

    /**
     * Generate ETag for content
     */
    private function generateETag(string $content, string $encoding): string
    {
        // Use xxh128 if available (PHP 8.1+), fallback to md5 for PHP 8.0
        $hash = function_exists('hash') && in_array('xxh128', hash_algos(), true)
            ? hash('xxh128', $content)
            : md5($content);

        return '"' . $hash . '-' . $encoding . '"';
    }

    /**
     * Check if ETag matches and return early if possible
     */
    private function handleETagMatch(Request $request, string $etag): bool
    {
        $clientETag = $request->headers->get('If-None-Match');
        return $clientETag === $etag;
    }

    private function setCompressionHeaders(Response $response, int $length, string $encoding): void
    {
        $response->headers->add([
            'Content-Encoding' => $encoding,
            'Content-Length' => $length,
        ]);

        // Handle Vary header properly
        $vary = $response->headers->get('Vary', '');
        $variations = array_filter(array_map('trim', explode(',', $vary)));
        if (! in_array('Accept-Encoding', $variations)) {
            $variations[] = 'Accept-Encoding';
            $response->headers->set('Vary', implode(', ', $variations));
        }

        // Add cache control if not present
        if (!$response->headers->has('Cache-Control') && $this->shouldSetCacheControl()) {
            $maxAge = $this->cacheControlMaxAge();
            $response->headers->set('Cache-Control', "public, max-age=$maxAge");
        }
    }

    private function shouldCompress(Request $request, $response): bool
    {
        // check if the package is enabled
        if (!$this->shouldGzipResponse()) {
            return false;
        }

        // Environment checks
        if ($this->skipEnvironment()) {
            return false;
        }

        // if debug is enabled, we do not gzip the response
        if ($this->gzipDebugEnabled()) {
            return false;
        }

        // Response type checks for a steamed file we do not compress
        if (!$this->isCompressibleResponseType($response)) {
            return false;
        }

        // Client capability checks
        if (!$this->clientSupportsCompression($request)) {
            return false;
        }

        // Existing encoding check
        if ($response instanceof Response && $response->headers->has('Content-Encoding')) {
            return false;
        }

        // Don't compress when client sent Range requests (byte ranges)
        if ($request->headers->has('Range')) {
            return false;
        }

        // if there is a minimum content length, we do not compress the response
        if (!$this->hasMinimumContentLength($response)) {
            return false;
        }

        // Content type checks
        if (!$this->shouldCompressContentType($response)) {
            return false;
        }

        // Check if path is excluded
        if ($this->isPathExcluded($request)) {
            return false;
        }

        // Don't compress redirects
        if ($response->isRedirection()) {
            return false;
        }

        return true;
    }

    /**
     * Check if current environment should skip compression
     */
    private function skipEnvironment(): bool
    {
        $skipLocal = $this->config['skip_local'] ?? true;
        $skipTesting = $this->config['skip_testing'] ?? true;

        if ($skipLocal && app()->isLocal()) {
            return true;
        }

        if ($skipTesting && app()->runningUnitTests()) {
            return true;
        }

        return false;
    }

    /**
     * Check if response type can be compressed
     */
    private function isCompressibleResponseType($response): bool
    {
        return $response instanceof Response
            && !$response instanceof BinaryFileResponse
            && !$response instanceof StreamedResponse;
    }

    /**
     * Check if client supports gzip
     */
    private function clientSupportsCompression(Request $request): bool
    {
        $acceptEncoding = $request->headers->get('Accept-Encoding', '');

        // Check for Brotli support and gZip
        if (
            (str_contains($acceptEncoding, 'br') && $this->hasBrotliSupport()) ||
            (str_contains($acceptEncoding, 'gzip') && $this->hasGzipSupport())
        ) {
            return true;
        }

        return false;
    }

    /**
     * Check if Brotli is available (cache the result)
     */
    private function hasBrotliSupport(): bool
    {
        if ($this->hasBrotli === null) {
            $this->hasBrotli = function_exists('brotli_compress');
        }
        return $this->hasBrotli;
    }

    /**
     * Check if Gzip is available (cache the result)
     */
    private function hasGzipSupport(): bool
    {
        if ($this->hasGzip === null) {
            $this->hasGzip = function_exists('gzencode');
        }
        return $this->hasGzip;
    }


    /**
     * Check if path is excluded from compression
     */
    private function isPathExcluded(Request $request): bool
    {
        $excludedPaths = $this->config['excluded_paths'] ?? [];
        $currentPath = $request->path();

        if (empty($excludedPaths)) {
            return false;
        }

        foreach ($excludedPaths as $pattern) {
            if (Str::is($pattern, $currentPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the content type should be compressed
     */
    private function shouldCompressContentType(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type', '');
        if (empty($contentType)) {
            return false;
        }

        $nonCompressibleTypes = $this->config['excluded_mime_types'] ?? [];
        if (Str::startsWith($contentType, $nonCompressibleTypes)) {
            return false;
        }

        // Only compress known compressible types
        $compressibleTypes = $this->config['compressible_mime_types'] ?? $this->getDefaultCompressibleTypes();

        return $this->matchesMimeType($contentType, $compressibleTypes);
    }

    /**
     * Check if content type matches any of the given patterns
     */
    private function matchesMimeType(string $contentType, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (Str::startsWith($contentType, $pattern)) {
                return true;
            }
        }
        return false;
    }

    private function getDefaultCompressibleTypes(): array
    {
        return [
            'text/',
            'application/json',
            'application/javascript',
            'application/x-javascript',
            'application/xml',
            'application/rss+xml',
            'application/atom+xml',
            'application/xhtml+xml',
            'application/ld+json',
            'image/svg+xml',
            'font/woff2',
        ];
    }

    /**
     * Prepare content for compression (especially important for CSS)
     */
    private function prepareContentForCompression(string $content, Response $response): string
    {
        $contentType = $response->headers->get('Content-Type', '');

        if (Str::startsWith($contentType, ['text/css', 'text/javascript', 'application/javascript'])) {
            // Ensure UTF-8 encoding for CSS
            if (!mb_check_encoding($content, 'UTF-8')) {
                $content = mb_convert_encoding($content, 'UTF-8', 'auto');
            }

            // Ensure the charset is set
            if (!Str::contains($contentType, 'charset')) {
                $charset = Str::startsWith($contentType, 'text/css') ? 'text/css' :
                    (Str::startsWith($contentType, 'text/javascript') ? 'text/javascript' : 'application/javascript');
                $response->headers->set('Content-Type', $charset . '; charset=UTF-8');
            }
        }

        return $content;
    }

    /**
     * Check if compression ratio is worthwhile
     */
    private function compressionWorthwhile(string $original, string $compressed): bool
    {
        $originalSize = strlen($original);
        $compressedSize = strlen($compressed);

        // Don't use compression if it actually increases size
        if ($compressedSize >= $originalSize) {
            return false;
        }

        $ratio = $compressedSize / $originalSize;
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
        $savedKb = round($saved / 1024, 2);

        Log::debug("Gzip: {$originalSize}B → {$compressedSize}B ($ratio%), saved {$savedKb}KB");
    }

    /**
     * Check if response meets minimum content length
     */
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
    private function compressLevel(): int
    {
        $level = (int) ($this->config['level'] ?? 5);
        return max(1, min(9, $level));
    }

    /**
     * Check if logging is enabled
     */
    private function shouldLog(): bool
    {
        return (bool) ($this->config['log'] ?? false);
    }

    private function shouldSetCacheControl(): bool
    {
        return (bool) ($this->config['set_cache_control'] ?? false);
    }

    private function cacheControlMaxAge(): int
    {
        return (int) ($this->config['cache_max_age'] ?? 86400);
    }

    /**
     * Get the gzip debug enabled.
     * If the debugger is enabled, we do not gzip the response.
     *
     * @return bool
     */
    private function gzipDebugEnabled(): bool
    {
        return (bool) ($this->config['debug'] ?? false);
    }

    private function getBestEncoding(Request $request): ?string
    {
        $acceptEncoding = $request->headers->get('Accept-Encoding', '');

        // Prefer Brotli over Gzip if supported
        if (str_contains($acceptEncoding, 'br') && $this->hasBrotliSupport()) {
            return 'br';
        }

        if (str_contains($acceptEncoding, 'gzip') && $this->hasGzipSupport()) {
            return 'gzip';
        }

        return null;
    }

    /**
     * Compress content based on encoding method
     */
    private function compressContent(string $content, string $encoding): string|false
    {
        if ($encoding === 'br') {
            return $this->compressBrotli($content);
        }
        
        if ($encoding === 'gzip') {
            return $this->compressGzip($content);
        }
        
        return false;
    }

    /**
     * Compress content using Brotli
     */
    private function compressBrotli(string $content): string|false
    {
        if (!$this->hasBrotliSupport()) {
            return false;
        }

        return brotli_compress($content,  $this->compressLevel());
    }

    /**
     * Compress content using Gzip
     */
    private function compressGzip(string $content): string|false
    {
        if (!$this->hasGzipSupport()) {
            return false;
        }

        return gzencode($content, $this->compressLevel());
    }

}
