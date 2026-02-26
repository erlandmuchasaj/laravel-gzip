# Laravel Gzip

Laravel Gzip is a simple and effective way to gzip your response for a better performance.

## Installation

### Installing the Package
You can install the package via composer:

```bash
composer require erlandmuchasaj/laravel-gzip
```

### Installing Brotli Extension (Optional but Recommended)
Brotli provides 15-20% better compression than Gzip and is supported by all modern browsers.
To install the Brotli extension, you can use PECL:

#### macOS (using Homebrew):
```bash
pecl install brotli
``` 

#### Ubuntu/Debian:
```bash
sudo apt-get install php-brotli
# or
sudo pecl install brotli
```

> [!NOTE]  
> If Brotli is not available, the package will automatically fall back to Gzip compression.
> 

## Config file
Publish the configuration file using artisan.

```bash
php artisan vendor:publish --provider="ErlandMuchasaj\LaravelGzip\GzipServiceProvider"
```

## Usage

This package has a very easy and straight-forward usage. 

### Laravel v11+
Just add the middleware in `bootstrap/app.php`, like so:
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        // ...
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(\ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse::class);
    })
    // ...
```

### Laravel v10 and older
Just add the middleware to the `$middleware` array in `app/Http/Kernel.php` like so:
```php

/**
 * The application's global HTTP middleware stack.
 *
 * These middleware are run during every request to your application.
 *
 * @var array<int, class-string|string>
 */
protected $middleware = [
    \ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse::class,
    //...
];
```

> [!IMPORTANT]
> In a previous version, we recommended adding the middleware to the `web` middleware group. **Now**, we recommend adding
> to global `$middleware` because we want to apply gzip to all requests. Additionally, registering the middleware in the 
> `web` group caused debugger to break.
> 
> **Also**, if you are using `spatie/laravel-cookie-consent` package, you should register this middleware before the 
> `\Spatie\CookieConsent\CookieConsentMiddleware::class` middleware.


> [!PS]
> To see the package working in production-like mode, use `APP_ENV=production` (double-check typos) and `APP_DEBUG=false`.

### Important for Laravel v11+ / v12

If compression does not apply after changing environment/config values, clear cached config:

```bash
php artisan optimize:clear
```

When testing locally you can force compression:

```env
GZIP_FORCE=true
```


That's it! Now your responses will be gzipped.

## Troubleshooting

If you still don't see `Content-Encoding: gzip` or `Content-Encoding: br`, check:

1. The request includes `Accept-Encoding: gzip` (or `br`).
2. Response content is larger than `GZIP_MIN_LENGTH` (default is `256`).
3. Route/path is not excluded by `excluded_paths`.
4. MIME type is compressible.
5. Config cache is cleared (`php artisan optimize:clear`) after env changes.

## Benchmark

I tested this package with a fresh installed laravel in homepage and got:

`No Gzip => 72.9kb`

`With Gzip => 19.2kb *`


---

## Support me

I invest a lot of time and resources into creating [best in class open source packages](https://github.com/erlandmuchasaj?tab=repositories).

If you found this package helpful you can show support by clicking on the following button below and donating some amount to help me work on these projects frequently.

<a href="https://www.buymeacoffee.com/erland" target="_blank">
    <img src="https://www.buymeacoffee.com/assets/img/guidelines/download-assets-2.svg" style="height: 45px; border-radius: 12px" alt="buy me a coffee"/>
</a>

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please see [SECURITY](SECURITY.md) for details.

## Credits

- [Erland Muchasaj](https://github.com/erlandmuchasaj)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
