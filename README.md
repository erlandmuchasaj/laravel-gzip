# Laravel Gzip

Laravel Gzip is a simple and effective way to gzip your response for a better performance.

## Installation

You can install the package via composer:

```bash
composer require erlandmuchasaj/laravel-gzip
```

## Config file
Publish the configuration file using artisan.

```bash
php artisan vendor:publish --provider="ErlandMuchasaj\LaravelGzip\GzipServiceProvider"
```

## Usage

This package has a very easy and straight-forward usage. 
Just add the middleware to the `web` middleware group in `app/Http/Kernel.php`
like so:

```php

protected $middlewareGroups = [
    'web' => [
        //...
        \ErlandMuchasaj\LaravelGzip\Middleware\GzipEncodeResponse::class,
    ],
];

```

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
