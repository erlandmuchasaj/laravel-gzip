<?php

namespace ErlandMuchasaj\LaravelGzip\Tests\Middleware;

use ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse;
use ErlandMuchasaj\LaravelGzip\Tests\TestCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
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
                ['HTTP_ACCEPT_ENCODING' => 'brotli']
            ],
            [
                ['HTTP_ACCEPT_ENCODING'=> 'gzip']
            ],
        ];
    }

    public static function gzipEnabledDataProvider()
    {
        return [
            [
                false
            ],
            [
                true
            ],
        ];
    }

    public static function gzipCompressionLevelDataProvider()
    {
        return [
            [
                1
            ],
            [
                9
            ]
        ];
    }

    public static function environmentDataProvider()
    {
        return [
            [
                'other'
            ],
            [
                'production'
            ],
        ];
    }

    public static function debugIsEnabledDataProvider()
    {
        return [
            [
                true
            ],
            [
                false
            ],
        ];
    }

    public static function responseDataProvider()
    {
        return [
            [
                new Response('ok'),
            ],
            [
                new StreamedResponse(function () {
                    return 'ok';
                }),
            ],
            [
                new BinaryFileResponse(__DIR__ . '/example.txt'),
            ],
            [
                new JsonResponse(['test' => 'test']),
            ],
            [
                new Response(),
            ],
        ];
    }

    public static function gzipDataProvider()
    {
        return DataProviderJoin::cross(
            self::gzipEnabledDataProvider(),
            self::environmentDataProvider(),
            self::debugIsEnabledDataProvider(),
            self::gzipAcceptEncodingDataProvider(),
            self::responseDataProvider(),
            self::gzipCompressionLevelDataProvider(),
        );
    }

    #[Test]
    #[DataProvider('gzipDataProvider')]
    public function it_behaves_as_expected(
        ?bool $gzipEnabled,
        string $environment,
        bool $debugEnabled,
        array $acceptEncoding,
        SymfonyResponse $middlewareResponse,
        ?int $gzipCompressionLevel,
    ): void
    {
        config([
            'laravel-gzip.enabled' => $gzipEnabled,
            'laravel-gzip.level' => $gzipCompressionLevel,
            'laravel-gzip.debug' => $debugEnabled,
        ]);

        $this->app['env'] = $environment;

        $request = Request::create(
            uri: '/test',
            server: $acceptEncoding
        );

        $responseCanBeGzipped = ! in_array(
            get_class($middlewareResponse),
            [BinaryFileResponse::class, StreamedResponse::class]
        );
        $response = null;
        if ($responseCanBeGzipped) {
            $response = $middlewareResponse->getContent();
        }

        $middleware = new GzipEncodeResponse();

        /** @var Response $result */
        $result = $middleware->handle($request, function () use ($middlewareResponse) {
            return $middlewareResponse;
        });

        $this->assertEquals(Response::HTTP_OK, $result->getStatusCode());

        $acceptEncodingIsGzip = isset($acceptEncoding['HTTP_ACCEPT_ENCODING'])
            && $acceptEncoding['HTTP_ACCEPT_ENCODING'] === 'gzip';

        $responseShouldBeGzipped = $gzipEnabled
            && $acceptEncodingIsGzip
            && $environment === 'production'
            && ! $debugEnabled
            && $responseCanBeGzipped
            && ! empty($middlewareResponse->getContent());

        if ($responseShouldBeGzipped) {
            $this->assertEquals('gzip',$result->headers->get('Content-Encoding'));
            $this->assertEquals(
                md5(
                    gzencode(
                        $response,
                        $gzipCompressionLevel,
                    )
                ),
                md5($result->getContent()),
            );
        } else {
            $this->assertNull($result->headers->get('Content-Encoding'));
            $this->assertEquals($response, $result->getContent());
        }
    }
}
