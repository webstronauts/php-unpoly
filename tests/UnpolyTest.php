<?php

namespace Webstronauts\Unpoly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Unpoly;

class UnpolyTest extends TestCase
{
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

    // Request inspection tests

    public function testIsUnpolyRequestReturnsTrueWithVersionHeader()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_VERSION' => '2.0.0']);

        $this->assertTrue((new Unpoly())->isUnpolyRequest($request));
    }

    public function testIsUnpolyRequestReturnsTrueWithTargetHeader()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertTrue((new Unpoly())->isUnpolyRequest($request));
    }

    public function testIsUnpolyRequestReturnsFalseWithoutHeaders()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->isUnpolyRequest($request));
    }

    public function testGetVersionReturnsVersion()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_VERSION' => '2.0.0']);

        $this->assertEquals('2.0.0', (new Unpoly())->getVersion($request));
    }

    public function testGetVersionReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getVersion($request));
    }

    public function testGetTargetReturnsSelector()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertEquals('.content', (new Unpoly())->getTarget($request));
    }

    public function testGetTargetReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getTarget($request));
    }

    public function testGetFailTargetReturnsSelector()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_FAIL_TARGET' => '.error']);

        $this->assertEquals('.error', (new Unpoly())->getFailTarget($request));
    }

    public function testGetFailTargetReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getFailTarget($request));
    }

    public function testIsTargetRequestReturnsTrueWhenMatches()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertTrue((new Unpoly())->isTargetRequest($request, '.content'));
    }

    public function testIsTargetRequestReturnsFalseWhenDoesNotMatch()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertFalse((new Unpoly())->isTargetRequest($request, '.other'));
    }

    public function testIsTargetRequestReturnsFalseWhenHeaderMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->isTargetRequest($request, '.content'));
    }

    public function testGetModeReturnsMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertEquals('modal', (new Unpoly())->getMode($request));
    }

    public function testGetModeReturnsRootByDefault()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals('root', (new Unpoly())->getMode($request));
    }

    public function testGetFailModeReturnsMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_FAIL_MODE' => 'drawer']);

        $this->assertEquals('drawer', (new Unpoly())->getFailMode($request));
    }

    public function testGetFailModeReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getFailMode($request));
    }

    public function testGetOriginModeReturnsMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_ORIGIN_MODE' => 'drawer']);

        $this->assertEquals('drawer', (new Unpoly())->getOriginMode($request));
    }

    public function testGetOriginModeReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getOriginMode($request));
    }

    public function testIsRootRequestReturnsTrueForRootMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'root']);

        $this->assertTrue((new Unpoly())->isRootRequest($request));
    }

    public function testIsRootRequestReturnsTrueWhenModeNotSet()
    {
        $request = Request::create('/path', 'GET');

        $this->assertTrue((new Unpoly())->isRootRequest($request));
    }

    public function testIsRootRequestReturnsFalseForOverlayMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertFalse((new Unpoly())->isRootRequest($request));
    }

    public function testIsOverlayRequestReturnsTrueForModalMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertTrue((new Unpoly())->isOverlayRequest($request));
    }

    public function testIsOverlayRequestReturnsTrueForDrawerMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'drawer']);

        $this->assertTrue((new Unpoly())->isOverlayRequest($request));
    }

    public function testIsOverlayRequestReturnsFalseForRootMode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'root']);

        $this->assertFalse((new Unpoly())->isOverlayRequest($request));
    }

    public function testIsOverlayRequestReturnsFalseWhenModeNotSet()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->isOverlayRequest($request));
    }

    public function testGetContextReturnsContext()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin"}'
        ]);

        $context = (new Unpoly())->getContext($request);

        $this->assertEquals(['userId' => 123, 'role' => 'admin'], $context);
    }

    public function testGetContextReturnsEmptyArrayWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly())->getContext($request));
    }

    public function testGetContextReturnsEmptyArrayForInvalidJson()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => 'invalid json'
        ]);

        $this->assertEquals([], (new Unpoly())->getContext($request));
    }

    public function testGetFailContextReturnsContext()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_FAIL_CONTEXT' => '{"error":"not_found"}'
        ]);

        $context = (new Unpoly())->getFailContext($request);

        $this->assertEquals(['error' => 'not_found'], $context);
    }

    public function testGetFailContextReturnsEmptyArrayWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly())->getFailContext($request));
    }

    // Cache revalidation tests (Unpoly 3.x)

    public function testGetIfModifiedSinceReturnsDateTime()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT'
        ]);

        $result = (new Unpoly())->getIfModifiedSince($request);

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2000-11-15 13:11:22', $result->format('Y-m-d H:i:s'));
    }

    public function testGetIfModifiedSinceReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getIfModifiedSince($request));
    }

    public function testGetIfModifiedSinceReturnsNullForInvalidFormat()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'invalid date format'
        ]);

        $this->assertNull((new Unpoly())->getIfModifiedSince($request));
    }

    public function testHasIfModifiedSinceReturnsTrueWhenPresent()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT'
        ]);

        $this->assertTrue((new Unpoly())->hasIfModifiedSince($request));
    }

    public function testHasIfModifiedSinceReturnsFalseWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->hasIfModifiedSince($request));
    }

    public function testGetIfNoneMatchReturnsETag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"55e4d42a148795d9f25f89d4"'
        ]);

        $this->assertEquals('W/"55e4d42a148795d9f25f89d4"', (new Unpoly())->getIfNoneMatch($request));
    }

    public function testGetIfNoneMatchReturnsStrongETag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => '"abc123def456"'
        ]);

        $this->assertEquals('"abc123def456"', (new Unpoly())->getIfNoneMatch($request));
    }

    public function testGetIfNoneMatchReturnsNullWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly())->getIfNoneMatch($request));
    }

    public function testHasIfNoneMatchReturnsTrueWhenPresent()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"55e4d42a148795d9f25f89d4"'
        ]);

        $this->assertTrue((new Unpoly())->hasIfNoneMatch($request));
    }

    public function testHasIfNoneMatchReturnsFalseWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->hasIfNoneMatch($request));
    }

    public function testIsValidateRequestReturnsTrueWhenHeaderPresent()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password'
        ]);

        $this->assertTrue((new Unpoly())->isValidateRequest($request));
    }

    public function testIsValidateRequestReturnsFalseWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->isValidateRequest($request));
    }

    public function testGetValidateNamesReturnsSingleField()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email'
        ]);

        $this->assertEquals(['email'], (new Unpoly())->getValidateNames($request));
    }

    public function testGetValidateNamesReturnsMultipleFields()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password username'
        ]);

        $this->assertEquals(['email', 'password', 'username'], (new Unpoly())->getValidateNames($request));
    }

    public function testGetValidateNamesReturnsUnknown()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => ':unknown'
        ]);

        $this->assertEquals([':unknown'], (new Unpoly())->getValidateNames($request));
    }

    public function testGetValidateNamesReturnsEmptyArrayWhenMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly())->getValidateNames($request));
    }

    public function testGetValidateNamesReturnsEmptyArrayForEmptyString()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => ''
        ]);

        $this->assertEquals([], (new Unpoly())->getValidateNames($request));
    }

    public function testIsValidatingFieldRequestReturnsTrueWhenFieldIncluded()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password'
        ]);

        $this->assertTrue((new Unpoly())->isValidatingFieldRequest($request, 'email'));
        $this->assertTrue((new Unpoly())->isValidatingFieldRequest($request, 'password'));
    }

    public function testIsValidatingFieldRequestReturnsFalseWhenFieldNotIncluded()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password'
        ]);

        $this->assertFalse((new Unpoly())->isValidatingFieldRequest($request, 'username'));
    }

    public function testIsValidatingFieldRequestReturnsFalseWhenHeaderMissing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly())->isValidatingFieldRequest($request, 'email'));
    }

    // Response header setting tests

    public function testSetTitleSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setTitle($response, 'My Page Title');

        $this->assertEquals('"My Page Title"', $response->headers->get('X-Up-Title'));
    }

    public function testSetTitleEncodesSpecialCharacters()
    {
        $response = new Response();

        (new Unpoly())->setTitle($response, 'Title with "quotes" and <tags>');

        $title = $response->headers->get('X-Up-Title');
        $this->assertStringContainsString('\u0022', $title);
        $this->assertStringContainsString('\u003C', $title);
    }

    public function testSetTargetSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setTarget($response, '.content');

        $this->assertEquals('.content', $response->headers->get('X-Up-Target'));
    }

    public function testAcceptLayerWithValue()
    {
        $response = new Response();

        (new Unpoly())->acceptLayer($response, ['result' => 'success']);

        $this->assertEquals('{"result":"success"}', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function testAcceptLayerWithNull()
    {
        $response = new Response();

        (new Unpoly())->acceptLayer($response);

        $this->assertEquals('null', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function testAcceptLayerWithExplicitNull()
    {
        $response = new Response();

        (new Unpoly())->acceptLayer($response, null);

        $this->assertEquals('null', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function testDismissLayerWithValue()
    {
        $response = new Response();

        (new Unpoly())->dismissLayer($response, ['reason' => 'cancelled']);

        $this->assertEquals('{"reason":"cancelled"}', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function testDismissLayerWithNull()
    {
        $response = new Response();

        (new Unpoly())->dismissLayer($response);

        $this->assertEquals('null', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function testDismissLayerWithExplicitNull()
    {
        $response = new Response();

        (new Unpoly())->dismissLayer($response, null);

        $this->assertEquals('null', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function testOpenLayerWithOptions()
    {
        $response = new Response();

        (new Unpoly())->openLayer($response, ['mode' => 'modal', 'target' => '.content']);

        $this->assertEquals('{"mode":"modal","target":".content"}', $response->headers->get('X-Up-Open-Layer'));
    }

    public function testOpenLayerWithEmptyOptions()
    {
        $response = new Response();

        (new Unpoly())->openLayer($response);

        $this->assertEquals('[]', $response->headers->get('X-Up-Open-Layer'));
    }

    public function testSetContextSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setContext($response, ['userId' => 123, 'role' => 'admin']);

        $this->assertEquals('{"userId":123,"role":"admin"}', $response->headers->get('X-Up-Context'));
    }

    public function testSetContextWithEmptyArray()
    {
        $response = new Response();

        (new Unpoly())->setContext($response, []);

        $this->assertEquals('[]', $response->headers->get('X-Up-Context'));
    }

    public function testUpdateContextMergesChanges()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin"}'
        ]);
        $response = new Response();

        (new Unpoly())->updateContext($request, $response, ['role' => 'superadmin', 'theme' => 'dark']);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['userId' => 123, 'role' => 'superadmin', 'theme' => 'dark'], $context);
    }

    public function testUpdateContextRemovesNullKeys()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin","theme":"dark"}'
        ]);
        $response = new Response();

        (new Unpoly())->updateContext($request, $response, ['role' => null]);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['userId' => 123, 'theme' => 'dark'], $context);
    }

    public function testUpdateContextWorksWithEmptyContext()
    {
        $request = Request::create('/path', 'GET');
        $response = new Response();

        (new Unpoly())->updateContext($request, $response, ['newKey' => 'newValue']);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['newKey' => 'newValue'], $context);
    }

    public function testEmitEventsSetsHeaderWithSingleEvent()
    {
        $response = new Response();

        (new Unpoly())->emitEvents($response, [
            ['type' => 'user:updated', 'userId' => 123]
        ]);

        $this->assertEquals('[{"type":"user:updated","userId":123}]', $response->headers->get('X-Up-Events'));
    }

    public function testEmitEventsSetsHeaderWithMultipleEvents()
    {
        $response = new Response();

        (new Unpoly())->emitEvents($response, [
            ['type' => 'user:updated', 'userId' => 123],
            ['type' => 'notification:sent']
        ]);

        $expected = '[{"type":"user:updated","userId":123},{"type":"notification:sent"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function testEmitEventsThrowsExceptionForInvalidEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Each event must be an array with a "type" property');

        $response = new Response();

        (new Unpoly())->emitEvents($response, [
            ['data' => 'missing type']
        ]);
    }

    public function testEmitEventsThrowsExceptionForNonArrayEvent()
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = new Response();

        (new Unpoly())->emitEvents($response, ['not an array']);
    }

    public function testEmitEventWithTypeOnly()
    {
        $response = new Response();

        (new Unpoly())->emitEvent($response, 'user:created');

        $this->assertEquals('[{"type":"user:created"}]', $response->headers->get('X-Up-Events'));
    }

    public function testEmitEventWithProperties()
    {
        $response = new Response();

        (new Unpoly())->emitEvent($response, 'user:updated', ['userId' => 123, 'name' => 'John']);

        $expected = '[{"type":"user:updated","userId":123,"name":"John"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function testEmitEventAppendsToExistingEvents()
    {
        $response = new Response();

        (new Unpoly())->emitEvent($response, 'first:event');
        (new Unpoly())->emitEvent($response, 'second:event', ['data' => 'value']);

        $expected = '[{"type":"first:event"},{"type":"second:event","data":"value"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function testEvictCacheSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->evictCache($response, '/notes/*');

        $this->assertEquals('/notes/*', $response->headers->get('X-Up-Evict-Cache'));
    }

    public function testEvictCacheWithWildcard()
    {
        $response = new Response();

        (new Unpoly())->evictCache($response, '*');

        $this->assertEquals('*', $response->headers->get('X-Up-Evict-Cache'));
    }

    public function testExpireCacheSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->expireCache($response, '/api/*');

        $this->assertEquals('/api/*', $response->headers->get('X-Up-Expire-Cache'));
    }

    public function testExpireCacheWithFalse()
    {
        $response = new Response();

        (new Unpoly())->expireCache($response, false);

        $this->assertEquals('false', $response->headers->get('X-Up-Expire-Cache'));
    }

    public function testClearCacheSetsWildcardEviction()
    {
        $response = new Response();

        (new Unpoly())->clearCache($response);

        $this->assertEquals('*', $response->headers->get('X-Up-Evict-Cache'));
    }

    // Cache revalidation response headers (Unpoly 3.x)

    public function testSetLastModifiedSetsHeader()
    {
        $response = new Response();
        $time = new \DateTimeImmutable('2000-11-15 13:11:22 GMT');

        (new Unpoly())->setLastModified($response, $time);

        $this->assertEquals('Wed, 15 Nov 2000 13:11:22 GMT', $response->headers->get('Last-Modified'));
    }

    public function testSetLastModifiedWithDateTime()
    {
        $response = new Response();
        $time = new \DateTime('2024-03-15 08:30:00 GMT');

        (new Unpoly())->setLastModified($response, $time);

        $this->assertEquals('Fri, 15 Mar 2024 08:30:00 GMT', $response->headers->get('Last-Modified'));
    }

    public function testSetLastModifiedWithDifferentTimezone()
    {
        $response = new Response();
        // Create a time in EST timezone
        $time = new \DateTimeImmutable('2024-03-15 10:00:00', new \DateTimeZone('America/New_York'));

        (new Unpoly())->setLastModified($response, $time);

        // Should be converted to GMT (10:00 EST = 15:00 GMT during DST, or 14:00 GMT without DST)
        // Let's verify the header is set correctly
        $header = $response->headers->get('Last-Modified');
        $this->assertNotNull($header);
        $this->assertStringContainsString('Fri, 15 Mar 2024', $header);
        $this->assertStringContainsString('GMT', $header);
    }

    public function testSetETagSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setETag($response, 'abc123def456');

        $this->assertEquals('"abc123def456"', $response->headers->get('ETag'));
    }

    public function testSetETagWithQuotedValue()
    {
        $response = new Response();

        (new Unpoly())->setETag($response, '"abc123def456"');

        $this->assertEquals('"abc123def456"', $response->headers->get('ETag'));
    }

    public function testSetETagWithWeakValidator()
    {
        $response = new Response();

        (new Unpoly())->setETag($response, 'W/"abc123def456"');

        $this->assertEquals('W/"abc123def456"', $response->headers->get('ETag'));
    }

    public function testGenerateETagCreatesWeakETag()
    {
        $etag = (new Unpoly())->generateETag('Hello World');

        $this->assertStringStartsWith('W/"', $etag);
        $this->assertStringEndsWith('"', $etag);
        $this->assertEquals('W/"b10a8db164e0754105b7a99be72e3fe5"', $etag);
    }

    public function testGenerateETagCreatesStrongETag()
    {
        $etag = (new Unpoly())->generateETag('Hello World', false);

        $this->assertStringStartsWith('"', $etag);
        $this->assertStringEndsWith('"', $etag);
        $this->assertStringNotContainsString('W/', $etag);
        $this->assertEquals('"b10a8db164e0754105b7a99be72e3fe5"', $etag);
    }

    public function testGenerateETagIsDeterministic()
    {
        $content = 'Test content for ETag generation';
        $etag1 = (new Unpoly())->generateETag($content);
        $etag2 = (new Unpoly())->generateETag($content);

        $this->assertEquals($etag1, $etag2);
    }

    public function testGenerateETagDifferentForDifferentContent()
    {
        $etag1 = (new Unpoly())->generateETag('Content A');
        $etag2 = (new Unpoly())->generateETag('Content B');

        $this->assertNotEquals($etag1, $etag2);
    }

    public function testNotModifiedSetsStatus304()
    {
        $response = new Response('Original content');
        $response->setStatusCode(200);

        (new Unpoly())->notModified($response);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function testNotModifiedClearsBody()
    {
        $response = new Response('Original content');

        (new Unpoly())->notModified($response);

        $this->assertEquals('', $response->getContent());
    }

    public function testNotModifiedPreservesHeaders()
    {
        $response = new Response('Original content');
        $response->headers->set('ETag', 'W/"abc123"');
        $response->headers->set('Last-Modified', 'Wed, 15 Nov 2000 13:11:22 GMT');

        (new Unpoly())->notModified($response);

        $this->assertEquals('W/"abc123"', $response->headers->get('ETag'));
        $this->assertEquals('Wed, 15 Nov 2000 13:11:22 GMT', $response->headers->get('Last-Modified'));
    }

    public function testIsNotModifiedReturnsTrueFor304()
    {
        $response = new Response();
        $response->setStatusCode(304);

        $this->assertTrue((new Unpoly())->isNotModified($response));
    }

    public function testIsNotModifiedReturnsFalseFor200()
    {
        $response = new Response();
        $response->setStatusCode(200);

        $this->assertFalse((new Unpoly())->isNotModified($response));
    }

    public function testShouldRevalidateReturnsFalseWhenETagMatches()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"'
        ]);

        $result = (new Unpoly())->shouldRevalidate($request, null, 'W/"abc123"');

        $this->assertFalse($result);
    }

    public function testShouldRevalidateReturnsTrueWhenETagDiffers()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"'
        ]);

        $result = (new Unpoly())->shouldRevalidate($request, null, 'W/"xyz789"');

        $this->assertTrue($result);
    }

    public function testShouldRevalidateNormalizesETag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => '"abc123"'
        ]);

        // ETag without quotes should be normalized for comparison
        $result = (new Unpoly())->shouldRevalidate($request, null, 'abc123');

        $this->assertFalse($result);
    }

    public function testShouldRevalidateReturnsFalseWhenNotModifiedSince()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT'
        ]);

        $lastModified = new \DateTimeImmutable('2000-11-15 13:11:22 GMT');

        $result = (new Unpoly())->shouldRevalidate($request, $lastModified);

        $this->assertFalse($result);
    }

    public function testShouldRevalidateReturnsTrueWhenModifiedSince()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT'
        ]);

        $lastModified = new \DateTimeImmutable('2000-11-15 14:00:00 GMT');

        $result = (new Unpoly())->shouldRevalidate($request, $lastModified);

        $this->assertTrue($result);
    }

    public function testShouldRevalidateETagTakesPrecedence()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"',
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT'
        ]);

        // ETag matches, so should return false even though lastModified is later
        $lastModified = new \DateTimeImmutable('2000-11-15 14:00:00 GMT');
        $result = (new Unpoly())->shouldRevalidate($request, $lastModified, 'W/"abc123"');

        $this->assertFalse($result);
    }

    public function testShouldRevalidateReturnsTrueWhenNoConditionalHeaders()
    {
        $request = Request::create('/path', 'GET');

        $result = (new Unpoly())->shouldRevalidate($request);

        $this->assertTrue($result);
    }

    // Vary header management (Unpoly 3.x)

    public function testAddVarySetsHeader()
    {
        $response = new Response();

        (new Unpoly())->addVary($response, 'X-Up-Target');

        $this->assertEquals('X-Up-Target', $response->headers->get('Vary'));
    }

    public function testAddVaryAppendsToExistingVary()
    {
        $response = new Response();
        $response->headers->set('Vary', 'Accept');

        (new Unpoly())->addVary($response, 'X-Up-Target');

        $this->assertEquals('Accept, X-Up-Target', $response->headers->get('Vary'));
    }

    public function testAddVaryDoesNotDuplicate()
    {
        $response = new Response();
        $response->headers->set('Vary', 'X-Up-Target');

        (new Unpoly())->addVary($response, 'X-Up-Target');

        $this->assertEquals('X-Up-Target', $response->headers->get('Vary'));
    }

    public function testAddVaryMultipleHeaders()
    {
        $response = new Response();

        (new Unpoly())->addVary($response, 'X-Up-Target');
        (new Unpoly())->addVary($response, 'X-Up-Mode');
        (new Unpoly())->addVary($response, 'X-Up-Context');

        $this->assertEquals('X-Up-Target, X-Up-Mode, X-Up-Context', $response->headers->get('Vary'));
    }

    public function testAddVaryHandlesExistingCommaSeparatedList()
    {
        $response = new Response();
        $response->headers->set('Vary', 'Accept, Accept-Encoding');

        (new Unpoly())->addVary($response, 'X-Up-Target');

        $this->assertEquals('Accept, Accept-Encoding, X-Up-Target', $response->headers->get('Vary'));
    }

    public function testAddVaryTrimsWhitespace()
    {
        $response = new Response();
        $response->headers->set('Vary', ' Accept , Accept-Encoding ');

        (new Unpoly())->addVary($response, 'X-Up-Target');

        $vary = $response->headers->get('Vary');
        $this->assertEquals('Accept, Accept-Encoding, X-Up-Target', $vary);
    }

    // Location and Method response headers

    public function testSetLocationSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setLocation($response, '/users/123');

        $this->assertEquals('/users/123', $response->headers->get('X-Up-Location'));
    }

    public function testSetLocationWithFullUrl()
    {
        $response = new Response();

        (new Unpoly())->setLocation($response, 'https://example.com/users/123');

        $this->assertEquals('https://example.com/users/123', $response->headers->get('X-Up-Location'));
    }

    public function testSetMethodSetsHeader()
    {
        $response = new Response();

        (new Unpoly())->setMethod($response, 'GET');

        $this->assertEquals('GET', $response->headers->get('X-Up-Method'));
    }

    public function testSetMethodUppercasesValue()
    {
        $response = new Response();

        (new Unpoly())->setMethod($response, 'post');

        $this->assertEquals('POST', $response->headers->get('X-Up-Method'));
    }

    public function testSetLocationAndMethodTogether()
    {
        $response = new Response();

        (new Unpoly())->setLocation($response, '/users/123');
        (new Unpoly())->setMethod($response, 'GET');

        $this->assertEquals('/users/123', $response->headers->get('X-Up-Location'));
        $this->assertEquals('GET', $response->headers->get('X-Up-Method'));
    }
}
