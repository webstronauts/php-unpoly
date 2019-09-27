# Unpoly

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webstronauts/unpoly.svg?style=flat-square)](https://packagist.org/packages/webstronauts/unpoly)
[![Build Status](https://img.shields.io/travis/com/webstronauts/php-unpoly/master.svg?style=flat-square)](https://travis-ci.com/webstronauts/php-unpoly)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/webstronauts/php-unpoly/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/webstronauts/php-unpoly)
[![Quality Score](https://img.shields.io/scrutinizer/g/webstronauts/php-unpoly.svg?style=flat-square)](https://scrutinizer-ci.com/g/webstronauts/php-unpoly)
[![StyleCI](https://github.styleci.io/repos/190603919/shield?branch=master)](https://github.styleci.io/repos/190603919)
[![Total Downloads](https://img.shields.io/packagist/dt/webstronauts/unpoly.svg?style=flat-square)](https://packagist.org/packages/webstronauts/unpoly)

Stack middleware for handling [Javascript Unpoly Framework](https://unpoly.com) requests.

## Installation

You can install the package via [Composer](https://getcomposer.org).

```bash
composer require webstronauts/unpoly
```

## Usage

You can manually decorate the response with the `Unpoly` object.

```php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Unpoly;

// ...

$unpoly = new Unpoly();
$unpoly->decorateResponse($request, $response);
```

### Stack Middleware

You can decorate the response using the supplied [Stack](http://stackphp.com) middleware.

```php
use Webstronauts\Unpoly\StackUnpoly;
use Webstronauts\Unpoly\Unpoly;

// ...

$app = new StackUnpoly($app, new Unpoly());
```

### Laravel

To use the package with Laravel, you'll have to wrap it around a middleware instance.

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Webstronauts\Unpoly\Unpoly as UnpolyMiddleware;

class Unpoly
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        (new UnpolyMiddleware)->decorateResponse($request, $response);

        return $response;
    }
}
```

Now use this middleware as described by the [Laravel documentation](https://laravel.com/docs/master/middleware).

```php
// Within App\Http\Kernel class...

protected $routeMiddleware = [
    // ...
    'unpoly' => \App\Http\Middleware\Unpoly::class,
];
```

#### Validation Errors

Whenever a form is submitted through Unpoly, the response is returned as JSON by default. This is because Laravel returns JSON formatted response for any request with the header `X-Requested-With` set to `XMLHttpRequest`. To make sure the application returns an HTML response for any validation errors, overwrite the `convertValidationExceptionToResponse` method in your `App\Exceptions\Handler` class.

```php
// Within App\Exceptions\Handler class...

protected function convertValidationExceptionToResponse(ValidationException $e, $request)
{
    if ($e->response) {
        return $e->response;
    }

    return $request->expectsJson() && ! $request->hasHeader('X-Up-Target')
        ? $this->invalidJson($request, $e)
        : $this->invalid($request, $e);
}
```

## Testing

``` bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

As it's just a simple port of Ruby to PHP code. All credits should go to the Unpoly team and their [unpoly](https://github.com/unpoly/unpoly) gem.

- [Robin van der Vleuten](https://github.com/robinvdvleuten)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
