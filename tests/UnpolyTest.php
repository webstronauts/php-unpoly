<?php

namespace Webstronauts\Unpoly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Unpoly;

class UnpolyTest extends TestCase
{
    public function testAppendsRequestHeadersToResponse()
    {
        $request = Request::create('/foo/bar', 'PUT');
        $response = new Response();

        (new Unpoly())->decorateResponse($request, $response);

        $this->assertEquals('/foo/bar', $response->headers->get('X-Up-Location'));
        $this->assertEquals('PUT', $response->headers->get('X-Up-Method'));
    }

    public function testAppendsRequestMethodCookieToResponse()
    {
        $request = Request::create('/foo/bar', 'PUT');

        $response = new Response();
        $response->headers->setCookie(new Cookie('foo', 'bar'));

        (new Unpoly())->decorateResponse($request, $response);

        $this->assertResponseHasCookie($response, '_up_method', 'PUT');
    }

    public function testRemovesRequestMethodCookieFromResponseWhenGetMethod()
    {
        $request = Request::create('/foo/bar', 'GET');

        $response = new Response();
        $response->headers->setCookie(new Cookie('_up_method', 'PUT'));

        (new Unpoly())->decorateResponse($request, $response);

        $this->assertResponseNotHasCookie($response, '_up_method', 'PUT');
    }

    private function assertResponseHasCookie(Response $response, string $name, string $value)
    {
        $this->assertNotNull($this->filterCookies($response, $name, $value));
    }

    private function assertResponseNotHasCookie(Response $response, string $name, string $value)
    {
        $this->assertNull($this->filterCookies($response, $name, $value));
    }

    private function filterCookies(Response $response, string $name, string $value)
    {
        $cookies = $response->headers->getCookies();

        $filteredCookies = array_filter($cookies, function (Cookie $cookie) use ($name, $value) {
            return $cookie->getName() === $name && $cookie->getValue() === $value;
        });

        return reset($filteredCookies) ?: null;
    }
}
