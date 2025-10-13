<?php

namespace Webstronauts\Unpoly\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Unpoly;

class UnpolyTest extends TestCase
{
    public function test_appends_request_method_cookie_to_response()
    {
        $request = Request::create('/foo/bar', 'PUT');

        $response = new Response;
        $response->headers->setCookie(new Cookie('foo', 'bar'));

        (new Unpoly)->decorateResponse($request, $response);

        $this->assertResponseHasCookie($response, '_up_method', 'PUT');
    }

    public function test_removes_request_method_cookie_from_response_when_get_method()
    {
        $request = Request::create('/foo/bar', 'GET');

        $response = new Response;
        $response->headers->setCookie(new Cookie('_up_method', 'PUT'));

        (new Unpoly)->decorateResponse($request, $response);

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

    public function test_is_unpoly_request_returns_true_with_version_header()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_VERSION' => '2.0.0']);

        $this->assertTrue((new Unpoly)->isUnpolyRequest($request));
    }

    public function test_is_unpoly_request_returns_true_with_target_header()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertTrue((new Unpoly)->isUnpolyRequest($request));
    }

    public function test_is_unpoly_request_returns_false_without_headers()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->isUnpolyRequest($request));
    }

    public function test_get_version_returns_version()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_VERSION' => '2.0.0']);

        $this->assertEquals('2.0.0', (new Unpoly)->getVersion($request));
    }

    public function test_get_version_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getVersion($request));
    }

    public function test_get_target_returns_selector()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertEquals('.content', (new Unpoly)->getTarget($request));
    }

    public function test_get_target_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getTarget($request));
    }

    public function test_get_fail_target_returns_selector()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_FAIL_TARGET' => '.error']);

        $this->assertEquals('.error', (new Unpoly)->getFailTarget($request));
    }

    public function test_get_fail_target_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getFailTarget($request));
    }

    public function test_is_target_request_returns_true_when_matches()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertTrue((new Unpoly)->isTargetRequest($request, '.content'));
    }

    public function test_is_target_request_returns_false_when_does_not_match()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_TARGET' => '.content']);

        $this->assertFalse((new Unpoly)->isTargetRequest($request, '.other'));
    }

    public function test_is_target_request_returns_false_when_header_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->isTargetRequest($request, '.content'));
    }

    public function test_get_mode_returns_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertEquals('modal', (new Unpoly)->getMode($request));
    }

    public function test_get_mode_returns_root_by_default()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals('root', (new Unpoly)->getMode($request));
    }

    public function test_get_fail_mode_returns_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_FAIL_MODE' => 'drawer']);

        $this->assertEquals('drawer', (new Unpoly)->getFailMode($request));
    }

    public function test_get_fail_mode_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getFailMode($request));
    }

    public function test_get_origin_mode_returns_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_ORIGIN_MODE' => 'drawer']);

        $this->assertEquals('drawer', (new Unpoly)->getOriginMode($request));
    }

    public function test_get_origin_mode_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getOriginMode($request));
    }

    public function test_is_root_request_returns_true_for_root_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'root']);

        $this->assertTrue((new Unpoly)->isRootRequest($request));
    }

    public function test_is_root_request_returns_true_when_mode_not_set()
    {
        $request = Request::create('/path', 'GET');

        $this->assertTrue((new Unpoly)->isRootRequest($request));
    }

    public function test_is_root_request_returns_false_for_overlay_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertFalse((new Unpoly)->isRootRequest($request));
    }

    public function test_is_overlay_request_returns_true_for_modal_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'modal']);

        $this->assertTrue((new Unpoly)->isOverlayRequest($request));
    }

    public function test_is_overlay_request_returns_true_for_drawer_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'drawer']);

        $this->assertTrue((new Unpoly)->isOverlayRequest($request));
    }

    public function test_is_overlay_request_returns_false_for_root_mode()
    {
        $request = Request::create('/path', 'GET', [], [], [], ['HTTP_X_UP_MODE' => 'root']);

        $this->assertFalse((new Unpoly)->isOverlayRequest($request));
    }

    public function test_is_overlay_request_returns_false_when_mode_not_set()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->isOverlayRequest($request));
    }

    public function test_get_context_returns_context()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin"}',
        ]);

        $context = (new Unpoly)->getContext($request);

        $this->assertEquals(['userId' => 123, 'role' => 'admin'], $context);
    }

    public function test_get_context_returns_empty_array_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly)->getContext($request));
    }

    public function test_get_context_returns_empty_array_for_invalid_json()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => 'invalid json',
        ]);

        $this->assertEquals([], (new Unpoly)->getContext($request));
    }

    public function test_get_fail_context_returns_context()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_FAIL_CONTEXT' => '{"error":"not_found"}',
        ]);

        $context = (new Unpoly)->getFailContext($request);

        $this->assertEquals(['error' => 'not_found'], $context);
    }

    public function test_get_fail_context_returns_empty_array_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly)->getFailContext($request));
    }

    // Cache revalidation tests (Unpoly 3.x)

    public function test_get_if_modified_since_returns_date_time()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT',
        ]);

        $result = (new Unpoly)->getIfModifiedSince($request);

        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertEquals('2000-11-15 13:11:22', $result->format('Y-m-d H:i:s'));
    }

    public function test_get_if_modified_since_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getIfModifiedSince($request));
    }

    public function test_get_if_modified_since_returns_null_for_invalid_format()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'invalid date format',
        ]);

        $this->assertNull((new Unpoly)->getIfModifiedSince($request));
    }

    public function test_has_if_modified_since_returns_true_when_present()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT',
        ]);

        $this->assertTrue((new Unpoly)->hasIfModifiedSince($request));
    }

    public function test_has_if_modified_since_returns_false_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->hasIfModifiedSince($request));
    }

    public function test_get_if_none_match_returns_e_tag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"55e4d42a148795d9f25f89d4"',
        ]);

        $this->assertEquals('W/"55e4d42a148795d9f25f89d4"', (new Unpoly)->getIfNoneMatch($request));
    }

    public function test_get_if_none_match_returns_strong_e_tag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => '"abc123def456"',
        ]);

        $this->assertEquals('"abc123def456"', (new Unpoly)->getIfNoneMatch($request));
    }

    public function test_get_if_none_match_returns_null_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertNull((new Unpoly)->getIfNoneMatch($request));
    }

    public function test_has_if_none_match_returns_true_when_present()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"55e4d42a148795d9f25f89d4"',
        ]);

        $this->assertTrue((new Unpoly)->hasIfNoneMatch($request));
    }

    public function test_has_if_none_match_returns_false_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->hasIfNoneMatch($request));
    }

    public function test_is_validate_request_returns_true_when_header_present()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password',
        ]);

        $this->assertTrue((new Unpoly)->isValidateRequest($request));
    }

    public function test_is_validate_request_returns_false_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->isValidateRequest($request));
    }

    public function test_get_validate_names_returns_single_field()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email',
        ]);

        $this->assertEquals(['email'], (new Unpoly)->getValidateNames($request));
    }

    public function test_get_validate_names_returns_multiple_fields()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password username',
        ]);

        $this->assertEquals(['email', 'password', 'username'], (new Unpoly)->getValidateNames($request));
    }

    public function test_get_validate_names_returns_unknown()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => ':unknown',
        ]);

        $this->assertEquals([':unknown'], (new Unpoly)->getValidateNames($request));
    }

    public function test_get_validate_names_returns_empty_array_when_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertEquals([], (new Unpoly)->getValidateNames($request));
    }

    public function test_get_validate_names_returns_empty_array_for_empty_string()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => '',
        ]);

        $this->assertEquals([], (new Unpoly)->getValidateNames($request));
    }

    public function test_is_validating_field_request_returns_true_when_field_included()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password',
        ]);

        $this->assertTrue((new Unpoly)->isValidatingFieldRequest($request, 'email'));
        $this->assertTrue((new Unpoly)->isValidatingFieldRequest($request, 'password'));
    }

    public function test_is_validating_field_request_returns_false_when_field_not_included()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_VALIDATE' => 'email password',
        ]);

        $this->assertFalse((new Unpoly)->isValidatingFieldRequest($request, 'username'));
    }

    public function test_is_validating_field_request_returns_false_when_header_missing()
    {
        $request = Request::create('/path', 'GET');

        $this->assertFalse((new Unpoly)->isValidatingFieldRequest($request, 'email'));
    }

    // Response header setting tests

    public function test_set_title_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setTitle($response, 'My Page Title');

        $this->assertEquals('"My Page Title"', $response->headers->get('X-Up-Title'));
    }

    public function test_set_title_encodes_special_characters()
    {
        $response = new Response;

        (new Unpoly)->setTitle($response, 'Title with "quotes" and <tags>');

        $title = $response->headers->get('X-Up-Title');
        $this->assertStringContainsString('\u0022', $title);
        $this->assertStringContainsString('\u003C', $title);
    }

    public function test_set_target_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setTarget($response, '.content');

        $this->assertEquals('.content', $response->headers->get('X-Up-Target'));
    }

    public function test_accept_layer_with_value()
    {
        $response = new Response;

        (new Unpoly)->acceptLayer($response, ['result' => 'success']);

        $this->assertEquals('{"result":"success"}', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function test_accept_layer_with_null()
    {
        $response = new Response;

        (new Unpoly)->acceptLayer($response);

        $this->assertEquals('null', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function test_accept_layer_with_explicit_null()
    {
        $response = new Response;

        (new Unpoly)->acceptLayer($response, null);

        $this->assertEquals('null', $response->headers->get('X-Up-Accept-Layer'));
    }

    public function test_dismiss_layer_with_value()
    {
        $response = new Response;

        (new Unpoly)->dismissLayer($response, ['reason' => 'cancelled']);

        $this->assertEquals('{"reason":"cancelled"}', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function test_dismiss_layer_with_null()
    {
        $response = new Response;

        (new Unpoly)->dismissLayer($response);

        $this->assertEquals('null', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function test_dismiss_layer_with_explicit_null()
    {
        $response = new Response;

        (new Unpoly)->dismissLayer($response, null);

        $this->assertEquals('null', $response->headers->get('X-Up-Dismiss-Layer'));
    }

    public function test_open_layer_with_options()
    {
        $response = new Response;

        (new Unpoly)->openLayer($response, ['mode' => 'modal', 'target' => '.content']);

        $this->assertEquals('{"mode":"modal","target":".content"}', $response->headers->get('X-Up-Open-Layer'));
    }

    public function test_open_layer_with_empty_options()
    {
        $response = new Response;

        (new Unpoly)->openLayer($response);

        $this->assertEquals('[]', $response->headers->get('X-Up-Open-Layer'));
    }

    public function test_set_context_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setContext($response, ['userId' => 123, 'role' => 'admin']);

        $this->assertEquals('{"userId":123,"role":"admin"}', $response->headers->get('X-Up-Context'));
    }

    public function test_set_context_with_empty_array()
    {
        $response = new Response;

        (new Unpoly)->setContext($response, []);

        $this->assertEquals('[]', $response->headers->get('X-Up-Context'));
    }

    public function test_update_context_merges_changes()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin"}',
        ]);
        $response = new Response;

        (new Unpoly)->updateContext($request, $response, ['role' => 'superadmin', 'theme' => 'dark']);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['userId' => 123, 'role' => 'superadmin', 'theme' => 'dark'], $context);
    }

    public function test_update_context_removes_null_keys()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_X_UP_CONTEXT' => '{"userId":123,"role":"admin","theme":"dark"}',
        ]);
        $response = new Response;

        (new Unpoly)->updateContext($request, $response, ['role' => null]);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['userId' => 123, 'theme' => 'dark'], $context);
    }

    public function test_update_context_works_with_empty_context()
    {
        $request = Request::create('/path', 'GET');
        $response = new Response;

        (new Unpoly)->updateContext($request, $response, ['newKey' => 'newValue']);

        $context = json_decode($response->headers->get('X-Up-Context'), true);
        $this->assertEquals(['newKey' => 'newValue'], $context);
    }

    public function test_emit_events_sets_header_with_single_event()
    {
        $response = new Response;

        (new Unpoly)->emitEvents($response, [
            ['type' => 'user:updated', 'userId' => 123],
        ]);

        $this->assertEquals('[{"type":"user:updated","userId":123}]', $response->headers->get('X-Up-Events'));
    }

    public function test_emit_events_sets_header_with_multiple_events()
    {
        $response = new Response;

        (new Unpoly)->emitEvents($response, [
            ['type' => 'user:updated', 'userId' => 123],
            ['type' => 'notification:sent'],
        ]);

        $expected = '[{"type":"user:updated","userId":123},{"type":"notification:sent"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function test_emit_events_throws_exception_for_invalid_event()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Each event must be an array with a "type" property');

        $response = new Response;

        (new Unpoly)->emitEvents($response, [
            ['data' => 'missing type'],
        ]);
    }

    public function test_emit_events_throws_exception_for_non_array_event()
    {
        $this->expectException(\InvalidArgumentException::class);

        $response = new Response;

        (new Unpoly)->emitEvents($response, ['not an array']);
    }

    public function test_emit_event_with_type_only()
    {
        $response = new Response;

        (new Unpoly)->emitEvent($response, 'user:created');

        $this->assertEquals('[{"type":"user:created"}]', $response->headers->get('X-Up-Events'));
    }

    public function test_emit_event_with_properties()
    {
        $response = new Response;

        (new Unpoly)->emitEvent($response, 'user:updated', ['userId' => 123, 'name' => 'John']);

        $expected = '[{"type":"user:updated","userId":123,"name":"John"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function test_emit_event_appends_to_existing_events()
    {
        $response = new Response;

        (new Unpoly)->emitEvent($response, 'first:event');
        (new Unpoly)->emitEvent($response, 'second:event', ['data' => 'value']);

        $expected = '[{"type":"first:event"},{"type":"second:event","data":"value"}]';
        $this->assertEquals($expected, $response->headers->get('X-Up-Events'));
    }

    public function test_evict_cache_sets_header()
    {
        $response = new Response;

        (new Unpoly)->evictCache($response, '/notes/*');

        $this->assertEquals('/notes/*', $response->headers->get('X-Up-Evict-Cache'));
    }

    public function test_evict_cache_with_wildcard()
    {
        $response = new Response;

        (new Unpoly)->evictCache($response, '*');

        $this->assertEquals('*', $response->headers->get('X-Up-Evict-Cache'));
    }

    public function test_expire_cache_sets_header()
    {
        $response = new Response;

        (new Unpoly)->expireCache($response, '/api/*');

        $this->assertEquals('/api/*', $response->headers->get('X-Up-Expire-Cache'));
    }

    public function test_expire_cache_with_false()
    {
        $response = new Response;

        (new Unpoly)->expireCache($response, false);

        $this->assertEquals('false', $response->headers->get('X-Up-Expire-Cache'));
    }

    public function test_clear_cache_sets_wildcard_eviction()
    {
        $response = new Response;

        (new Unpoly)->clearCache($response);

        $this->assertEquals('*', $response->headers->get('X-Up-Evict-Cache'));
    }

    // Cache revalidation response headers (Unpoly 3.x)

    public function test_set_last_modified_sets_header()
    {
        $response = new Response;
        $time = new \DateTimeImmutable('2000-11-15 13:11:22 GMT');

        (new Unpoly)->setLastModified($response, $time);

        $this->assertEquals('Wed, 15 Nov 2000 13:11:22 GMT', $response->headers->get('Last-Modified'));
    }

    public function test_set_last_modified_with_date_time()
    {
        $response = new Response;
        $time = new \DateTime('2024-03-15 08:30:00 GMT');

        (new Unpoly)->setLastModified($response, $time);

        $this->assertEquals('Fri, 15 Mar 2024 08:30:00 GMT', $response->headers->get('Last-Modified'));
    }

    public function test_set_last_modified_with_different_timezone()
    {
        $response = new Response;
        // Create a time in EST timezone
        $time = new \DateTimeImmutable('2024-03-15 10:00:00', new \DateTimeZone('America/New_York'));

        (new Unpoly)->setLastModified($response, $time);

        // Should be converted to GMT (10:00 EST = 15:00 GMT during DST, or 14:00 GMT without DST)
        // Let's verify the header is set correctly
        $header = $response->headers->get('Last-Modified');
        $this->assertNotNull($header);
        $this->assertStringContainsString('Fri, 15 Mar 2024', $header);
        $this->assertStringContainsString('GMT', $header);
    }

    public function test_set_e_tag_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setETag($response, 'abc123def456');

        $this->assertEquals('"abc123def456"', $response->headers->get('ETag'));
    }

    public function test_set_e_tag_with_quoted_value()
    {
        $response = new Response;

        (new Unpoly)->setETag($response, '"abc123def456"');

        $this->assertEquals('"abc123def456"', $response->headers->get('ETag'));
    }

    public function test_set_e_tag_with_weak_validator()
    {
        $response = new Response;

        (new Unpoly)->setETag($response, 'W/"abc123def456"');

        $this->assertEquals('W/"abc123def456"', $response->headers->get('ETag'));
    }

    public function test_generate_e_tag_creates_weak_e_tag()
    {
        $etag = (new Unpoly)->generateETag('Hello World');

        $this->assertStringStartsWith('W/"', $etag);
        $this->assertStringEndsWith('"', $etag);
        $this->assertEquals('W/"b10a8db164e0754105b7a99be72e3fe5"', $etag);
    }

    public function test_generate_e_tag_creates_strong_e_tag()
    {
        $etag = (new Unpoly)->generateETag('Hello World', false);

        $this->assertStringStartsWith('"', $etag);
        $this->assertStringEndsWith('"', $etag);
        $this->assertStringNotContainsString('W/', $etag);
        $this->assertEquals('"b10a8db164e0754105b7a99be72e3fe5"', $etag);
    }

    public function test_generate_e_tag_is_deterministic()
    {
        $content = 'Test content for ETag generation';
        $etag1 = (new Unpoly)->generateETag($content);
        $etag2 = (new Unpoly)->generateETag($content);

        $this->assertEquals($etag1, $etag2);
    }

    public function test_generate_e_tag_different_for_different_content()
    {
        $etag1 = (new Unpoly)->generateETag('Content A');
        $etag2 = (new Unpoly)->generateETag('Content B');

        $this->assertNotEquals($etag1, $etag2);
    }

    public function test_not_modified_sets_status304()
    {
        $response = new Response('Original content');
        $response->setStatusCode(200);

        (new Unpoly)->notModified($response);

        $this->assertEquals(304, $response->getStatusCode());
    }

    public function test_not_modified_clears_body()
    {
        $response = new Response('Original content');

        (new Unpoly)->notModified($response);

        $this->assertEquals('', $response->getContent());
    }

    public function test_not_modified_preserves_headers()
    {
        $response = new Response('Original content');
        $response->headers->set('ETag', 'W/"abc123"');
        $response->headers->set('Last-Modified', 'Wed, 15 Nov 2000 13:11:22 GMT');

        (new Unpoly)->notModified($response);

        $this->assertEquals('W/"abc123"', $response->headers->get('ETag'));
        $this->assertEquals('Wed, 15 Nov 2000 13:11:22 GMT', $response->headers->get('Last-Modified'));
    }

    public function test_is_not_modified_returns_true_for304()
    {
        $response = new Response;
        $response->setStatusCode(304);

        $this->assertTrue((new Unpoly)->isNotModified($response));
    }

    public function test_is_not_modified_returns_false_for200()
    {
        $response = new Response;
        $response->setStatusCode(200);

        $this->assertFalse((new Unpoly)->isNotModified($response));
    }

    public function test_should_revalidate_returns_false_when_e_tag_matches()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"',
        ]);

        $result = (new Unpoly)->shouldRevalidate($request, null, 'W/"abc123"');

        $this->assertFalse($result);
    }

    public function test_should_revalidate_returns_true_when_e_tag_differs()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"',
        ]);

        $result = (new Unpoly)->shouldRevalidate($request, null, 'W/"xyz789"');

        $this->assertTrue($result);
    }

    public function test_should_revalidate_normalizes_e_tag()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => '"abc123"',
        ]);

        // ETag without quotes should be normalized for comparison
        $result = (new Unpoly)->shouldRevalidate($request, null, 'abc123');

        $this->assertFalse($result);
    }

    public function test_should_revalidate_returns_false_when_not_modified_since()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT',
        ]);

        $lastModified = new \DateTimeImmutable('2000-11-15 13:11:22 GMT');

        $result = (new Unpoly)->shouldRevalidate($request, $lastModified);

        $this->assertFalse($result);
    }

    public function test_should_revalidate_returns_true_when_modified_since()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT',
        ]);

        $lastModified = new \DateTimeImmutable('2000-11-15 14:00:00 GMT');

        $result = (new Unpoly)->shouldRevalidate($request, $lastModified);

        $this->assertTrue($result);
    }

    public function test_should_revalidate_e_tag_takes_precedence()
    {
        $request = Request::create('/path', 'GET', [], [], [], [
            'HTTP_IF_NONE_MATCH' => 'W/"abc123"',
            'HTTP_IF_MODIFIED_SINCE' => 'Wed, 15 Nov 2000 13:11:22 GMT',
        ]);

        // ETag matches, so should return false even though lastModified is later
        $lastModified = new \DateTimeImmutable('2000-11-15 14:00:00 GMT');
        $result = (new Unpoly)->shouldRevalidate($request, $lastModified, 'W/"abc123"');

        $this->assertFalse($result);
    }

    public function test_should_revalidate_returns_true_when_no_conditional_headers()
    {
        $request = Request::create('/path', 'GET');

        $result = (new Unpoly)->shouldRevalidate($request);

        $this->assertTrue($result);
    }

    // Vary header management (Unpoly 3.x)

    public function test_add_vary_sets_header()
    {
        $response = new Response;

        (new Unpoly)->addVary($response, 'X-Up-Target');

        $this->assertEquals('X-Up-Target', $response->headers->get('Vary'));
    }

    public function test_add_vary_appends_to_existing_vary()
    {
        $response = new Response;
        $response->headers->set('Vary', 'Accept');

        (new Unpoly)->addVary($response, 'X-Up-Target');

        $this->assertEquals('Accept, X-Up-Target', $response->headers->get('Vary'));
    }

    public function test_add_vary_does_not_duplicate()
    {
        $response = new Response;
        $response->headers->set('Vary', 'X-Up-Target');

        (new Unpoly)->addVary($response, 'X-Up-Target');

        $this->assertEquals('X-Up-Target', $response->headers->get('Vary'));
    }

    public function test_add_vary_multiple_headers()
    {
        $response = new Response;

        (new Unpoly)->addVary($response, 'X-Up-Target');
        (new Unpoly)->addVary($response, 'X-Up-Mode');
        (new Unpoly)->addVary($response, 'X-Up-Context');

        $this->assertEquals('X-Up-Target, X-Up-Mode, X-Up-Context', $response->headers->get('Vary'));
    }

    public function test_add_vary_handles_existing_comma_separated_list()
    {
        $response = new Response;
        $response->headers->set('Vary', 'Accept, Accept-Encoding');

        (new Unpoly)->addVary($response, 'X-Up-Target');

        $this->assertEquals('Accept, Accept-Encoding, X-Up-Target', $response->headers->get('Vary'));
    }

    public function test_add_vary_trims_whitespace()
    {
        $response = new Response;
        $response->headers->set('Vary', ' Accept , Accept-Encoding ');

        (new Unpoly)->addVary($response, 'X-Up-Target');

        $vary = $response->headers->get('Vary');
        $this->assertEquals('Accept, Accept-Encoding, X-Up-Target', $vary);
    }

    // Location and Method response headers

    public function test_set_location_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setLocation($response, '/users/123');

        $this->assertEquals('/users/123', $response->headers->get('X-Up-Location'));
    }

    public function test_set_location_with_full_url()
    {
        $response = new Response;

        (new Unpoly)->setLocation($response, 'https://example.com/users/123');

        $this->assertEquals('https://example.com/users/123', $response->headers->get('X-Up-Location'));
    }

    public function test_set_method_sets_header()
    {
        $response = new Response;

        (new Unpoly)->setMethod($response, 'GET');

        $this->assertEquals('GET', $response->headers->get('X-Up-Method'));
    }

    public function test_set_method_uppercases_value()
    {
        $response = new Response;

        (new Unpoly)->setMethod($response, 'post');

        $this->assertEquals('POST', $response->headers->get('X-Up-Method'));
    }

    public function test_set_location_and_method_together()
    {
        $response = new Response;

        (new Unpoly)->setLocation($response, '/users/123');
        (new Unpoly)->setMethod($response, 'GET');

        $this->assertEquals('/users/123', $response->headers->get('X-Up-Location'));
        $this->assertEquals('GET', $response->headers->get('X-Up-Method'));
    }
}
