# Unpoly

[![Latest Version on Packagist](https://img.shields.io/packagist/v/webstronauts/unpoly.svg?style=flat-square)](https://packagist.org/packages/webstronauts/unpoly)
[![Build Status](https://img.shields.io/github/actions/workflow/status/webstronauts/php-unpoly/run-tests.yml?branch=main&style=flat-square)](https://github.com/webstronauts/php-unpoly/actions?query=workflow%3Arun-tests)
[![StyleCI](https://github.styleci.io/repos/190603919/shield?branch=master)](https://github.styleci.io/repos/190603919)
[![Total Downloads](https://img.shields.io/packagist/dt/webstronauts/unpoly.svg?style=flat-square)](https://packagist.org/packages/webstronauts/unpoly)

Stack middleware for handling [Javascript Unpoly Framework](https://unpoly.com) requests.

<a href="https://webstronauts.com/">
    <img src="https://webstronauts.com/badges/sponsored-by-webstronauts.svg" alt="Sponsored by The Webstronauts" width="200" height="65">
</a>

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
<?php

// Within App\Http\Kernel class...

protected $routeMiddleware = [
    // ...
    'unpoly' => \App\Http\Middleware\Unpoly::class,
];
```

#### Validation Errors

Whenever a form is submitted through Unpoly, the response is returned as JSON by default. This is because Laravel returns JSON formatted response for any request with the header `X-Requested-With` set to `XMLHttpRequest`. To make sure the application returns an HTML response for any validation errors, overwrite the `convertValidationExceptionToResponse` method in your `App\Exceptions\Handler` class.

```php
<?php

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

#### Other HTTP Errors

If your Laravel session expires and a user attempts to navigate or perform an operating on the page using Unpoly, an abrupt JSON error response will be displayed to the user:

```
{'error': 'Unauthenticated.'}
```

To prevent this, create your own `Request` and extend Laravel's built-in `Illuminate\Http\Request`, and override the `expectsJson` method:

```php
namespace App\Http;

use Illuminate\Http\Request as BaseRequest;

class Request extends BaseRequest
{
    public function expectsJson()
    {
        if ($this->hasHeader('X-Up-Target')) {
            return false;
        }

        return parent::expectsJson();
    }
}
```

Then, navigate to your `public/index.php` file, and update the usage:


```php
// From...
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// To...
$response = $kernel->handle(
    $request = App\Http\Request::capture()
);
```

Now when a user session expires, the `<body>` of your page will be replaced with your login page, allowing users to sign back in without refreshing the page.

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
