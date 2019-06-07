<?php

namespace Webstronauts\Unpoly;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Unpoly
{
    /**
     * Response header to echo request's URL.
     *
     * @var string
     */
    const LOCATION_RESPONSE_HEADER = 'X-Up-Location';

    /**
     * Response header to echo request's method.
     *
     * @var string
     */
    const METHOD_RESPONSE_HEADER = 'X-Up-Method';

    /**
     * Cookie name to echo request's method.
     *
     * @var string
     */
    const METHOD_COOKIE_NAME = '_up_method';

    /**
     * Modifies the HTTP headers and cookies of the response so that it can be
     * properly handled by the Unpoly javascript.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function decorateResponse(Request $request, Response $response): void
    {
        $this->echoRequestHeaders($request, $response);
        $this->appendMethodCookie($request, $response);
    }

    /**
     * Unpoly requires these headers to detect redirects,
     * which are otherwise undetectable for an AJAX client.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function echoRequestHeaders(Request $request, Response $response): void
    {
        $response->headers->add([
            self::LOCATION_RESPONSE_HEADER => $request->getPathInfo(),
            self::METHOD_RESPONSE_HEADER => $request->getMethod(),
        ]);
    }

    /**
     * Unpoly requires this cookie to detect whether the initial page
     * load was requested using a non-GET method. In this case the Unpoly
     * framework will prevent itself from booting until it was loaded
     * from a GET request.
     *
     * @see https://github.com/rails/turbolinks/search?q=request_method&ref=cmdform
     * @see https://github.com/rails/turbolinks/blob/83d4b3d2c52a681f07900c28adb28bc8da604733/README.md#initialization
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    protected function appendMethodCookie(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET') && ! $request->headers->has('X-Up-Target')) {
            $response->headers->setCookie(new Cookie(self::METHOD_COOKIE_NAME, $request->getMethod()));
        } else {
            $response->headers->removeCookie(self::METHOD_COOKIE_NAME);
        }
    }
}
