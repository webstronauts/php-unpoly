<?php

namespace Webstronauts\Unpoly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Unpoly;

class UnpolyTest extends TestCase
{
    public function testChecksIfUnpolyRequestBasedOnVersion()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Version', '2.0.0');

        $this->assertTrue(Unpoly::isUnpolyRequest($request));
    }

    public function testChecksIfUnpolyRequestBasedOnTarget()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Target', '.css.selector');

        $this->assertTrue(Unpoly::isUnpolyRequest($request));
    }

    public function testVersionReturnsVersionFromHeader()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Version', '2.0.0');

        $this->assertEquals('2.0.0', Unpoly::getVersion($request));
    }

    public function testModeReturnsModeFromHeader()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Mode', 'replace');

        $this->assertEquals('replace', Unpoly::getMode($request));
    }

    public function testFailModeReturnsFailModeFromHeader()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Fail-Mode', 'replace');

        $this->assertEquals('replace', Unpoly::getFailMode($request));
    }

    public function testTargetReturnsSelectorFromHeader()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Target', '.css.selector');

        $this->assertEquals('.css.selector', Unpoly::getTarget($request));
    }

    public function testFailTargetReturnsSelectorFromHeader()
    {
        $request = Request::create('/foo/bar');
        $request->headers->set('X-Up-Fail-Target', '.css.selector');

        $this->assertEquals('.css.selector', Unpoly::getFailTarget($request));
    }

    public function testAppendsRequestHeadersToResponse()
    {
        $request = Request::create('/foo/bar?param=baz', 'PUT');
        $response = new Response();

        (new Unpoly())->decorateResponse($request, $response);

        $this->assertEquals('http://localhost/foo/bar?param=baz', $response->headers->get('X-Up-Location'));
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
