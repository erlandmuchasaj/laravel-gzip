<?php

namespace ErlandMuchasaj\LaravelGzip\Tests\Middleware;

use ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse;
use ErlandMuchasaj\LaravelGzip\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TRegx\PhpUnit\DataProviders\DataProvider as DataProviderJoin;

class GzipEncodeResponseTest extends TestCase
{
    public static function gzipAcceptEncodingDataProvider()
    {
        return [
            [
                []
            ],
            [
                ['HTTP_ACCEPT_ENCODING'=> 'gzip']
            ],
            [
                ['HTTP_ACCEPT_ENCODING'=> 'brotli']
            ]
        ];
    }

    public static function gzipEnabledDataProvider()
    {
        return [
            [
                null
            ],
            [
                true
            ],
            [
                false
            ]
        ];
    }

    public static function gzipCompressionLevelDataProvider()
    {
        return [
            [
                null
            ],
            [
                1
            ],
            [
                9
            ]
        ];
    }

    public static function gzipDataProvider()
    {
        return DataProviderJoin::cross(
            self::gzipAcceptEncodingDataProvider(),
            self::gzipEnabledDataProvider(),
            self::gzipCompressionLevelDataProvider()
        );
    }

    #[Test]
    #[DataProvider('gzipDataProvider')]
    public function it_behaves_as_expected($acceptEncoding, $gzipEnabled, $gzipCompressionLevel): void
    {
        if (! is_null($gzipEnabled)) {
            config([
                'laravel-gzip.enabled' => $gzipEnabled
            ]);
        }

        if (! is_null($gzipCompressionLevel)) {
            config([
                'laravel-gzip.level' => $gzipCompressionLevel
            ]);
        }

        $request = Request::create(
            uri: '/test',
            server: $acceptEncoding
        );

        $defaultCompressionLevel = 5;

        $response = "Example response";
        $compressedResponse = gzencode($response, $gzipCompressionLevel ?? $defaultCompressionLevel);

        $middleware = new GzipEncodeResponse();

        /** @var Response $result */
        $result = $middleware->handle($request, function () use ($response) {
            return response($response);
        });

        $this->assertEquals(Response::HTTP_OK,$result->getStatusCode());

        $acceptEncodingIsGzip = isset($acceptEncoding['HTTP_ACCEPT_ENCODING'])
            && $acceptEncoding['HTTP_ACCEPT_ENCODING'] === 'gzip';

        if ((is_null($gzipEnabled) || $gzipEnabled) && $acceptEncodingIsGzip) {
            $this->assertEquals('gzip',$result->headers->get('Content-Encoding'));
            $this->assertEquals(md5($compressedResponse), md5($result->getContent()));
        } else {
            $this->assertEmpty($result->headers->get('Content-Encoding'));
            $this->assertEquals($response, $result->getContent());
        }
    }
}
