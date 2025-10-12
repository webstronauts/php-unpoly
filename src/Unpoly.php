<?php

namespace Webstronauts\Unpoly;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webstronauts\Unpoly\Support\Json;

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

    // Unpoly 2.x Request Headers
    const VERSION_HEADER = 'X-Up-Version';
    const TARGET_HEADER = 'X-Up-Target';
    const FAIL_TARGET_HEADER = 'X-Up-Fail-Target';
    const MODE_HEADER = 'X-Up-Mode';
    const FAIL_MODE_HEADER = 'X-Up-Fail-Mode';
    const ORIGIN_MODE_HEADER = 'X-Up-Origin-Mode';
    const CONTEXT_HEADER = 'X-Up-Context';
    const FAIL_CONTEXT_HEADER = 'X-Up-Fail-Context';
    const VALIDATE_HEADER = 'X-Up-Validate';

    // Unpoly 2.x Response Headers
    const TITLE_HEADER = 'X-Up-Title';
    const EVENTS_HEADER = 'X-Up-Events';
    const ACCEPT_LAYER_HEADER = 'X-Up-Accept-Layer';
    const DISMISS_LAYER_HEADER = 'X-Up-Dismiss-Layer';
    const OPEN_LAYER_HEADER = 'X-Up-Open-Layer';
    const CONTEXT_RESPONSE_HEADER = 'X-Up-Context';
    const TARGET_RESPONSE_HEADER = 'X-Up-Target';
    const EVICT_CACHE_HEADER = 'X-Up-Evict-Cache';
    const EXPIRE_CACHE_HEADER = 'X-Up-Expire-Cache';

    /**
     * Modifies the HTTP cookies of the response so that it can be
     * properly handled by the Unpoly javascript.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function decorateResponse(Request $request, Response $response): void
    {
        $this->appendMethodCookie($request, $response);
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

    /**
     * Check if the request is from Unpoly.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isUnpolyRequest(Request $request): bool
    {
        return $request->headers->has(self::VERSION_HEADER)
            || $request->headers->has(self::TARGET_HEADER);
    }

    /**
     * Get the Unpoly version from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getVersion(Request $request): ?string
    {
        return $request->headers->get(self::VERSION_HEADER);
    }

    /**
     * Get the CSS selector that Unpoly is targeting for replacement.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getTarget(Request $request): ?string
    {
        return $request->headers->get(self::TARGET_HEADER);
    }

    /**
     * Get the CSS selector for the failure target.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getFailTarget(Request $request): ?string
    {
        return $request->headers->get(self::FAIL_TARGET_HEADER);
    }

    /**
     * Check if a specific selector is being targeted.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $selector
     * @return bool
     */
    public function isTargetRequest(Request $request, string $selector): bool
    {
        return $this->getTarget($request) === $selector;
    }

    /**
     * Get the layer mode from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string
     */
    public function getMode(Request $request): string
    {
        return $request->headers->get(self::MODE_HEADER, 'root');
    }

    /**
     * Get the failure mode from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getFailMode(Request $request): ?string
    {
        return $request->headers->get(self::FAIL_MODE_HEADER);
    }

    /**
     * Get the origin layer mode from the request.
     *
     * This is the mode of the layer where the interaction originated,
     * which may be different from the target layer.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getOriginMode(Request $request): ?string
    {
        return $request->headers->get(self::ORIGIN_MODE_HEADER);
    }

    /**
     * Check if the request is for the root layer.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isRootRequest(Request $request): bool
    {
        return $this->getMode($request) === 'root';
    }

    /**
     * Check if the request is for an overlay layer.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isOverlayRequest(Request $request): bool
    {
        return !$this->isRootRequest($request);
    }

    /**
     * Get the layer context from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return array
     */
    public function getContext(Request $request): array
    {
        return Json::decodeObject($request->headers->get(self::CONTEXT_HEADER)) ?? [];
    }

    /**
     * Get the failure context from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return array
     */
    public function getFailContext(Request $request): array
    {
        return Json::decodeObject($request->headers->get(self::FAIL_CONTEXT_HEADER)) ?? [];
    }

    /**
     * Get the If-Modified-Since timestamp from the request.
     *
     * Unpoly sends this header when reloading a fragment to check if content
     * has changed since the last request. This is part of standard HTTP cache
     * revalidation introduced in Unpoly 3.x.
     *
     * @see https://unpoly.com/If-Modified-Since
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return \DateTimeInterface|null
     */
    public function getIfModifiedSince(Request $request): ?\DateTimeInterface
    {
        $header = $request->headers->get('If-Modified-Since');

        if ($header === null) {
            return null;
        }

        // Parse RFC 1123 format: "Wed, 15 Nov 2000 13:11:22 GMT"
        $time = \DateTimeImmutable::createFromFormat('D, d M Y H:i:s \G\M\T', $header);

        return $time !== false ? $time : null;
    }

    /**
     * Check if the request includes an If-Modified-Since header.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function hasIfModifiedSince(Request $request): bool
    {
        return $request->headers->has('If-Modified-Since');
    }

    /**
     * Get the If-None-Match ETag from the request.
     *
     * Unpoly sends this header when reloading a fragment to check if the ETag
     * matches. This is part of standard HTTP cache revalidation introduced in
     * Unpoly 3.x. If both If-None-Match and If-Modified-Since are present,
     * If-None-Match takes precedence.
     *
     * @see https://unpoly.com/If-None-Match
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return string|null
     */
    public function getIfNoneMatch(Request $request): ?string
    {
        return $request->headers->get('If-None-Match');
    }

    /**
     * Check if the request includes an If-None-Match header.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function hasIfNoneMatch(Request $request): bool
    {
        return $request->headers->has('If-None-Match');
    }

    /**
     * Check if the request is a validation request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isValidateRequest(Request $request): bool
    {
        return $request->headers->has(self::VALIDATE_HEADER);
    }

    /**
     * Get the field names being validated from the request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return array
     */
    public function getValidateNames(Request $request): array
    {
        $validate = $request->headers->get(self::VALIDATE_HEADER);

        if ($validate === null || $validate === '') {
            return [];
        }

        // Handle :unknown validation origin
        if ($validate === ':unknown') {
            return [':unknown'];
        }

        // Field names are space-separated
        return array_filter(explode(' ', $validate));
    }

    /**
     * Check if a specific field is being validated.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  string  $fieldName
     * @return bool
     */
    public function isValidatingFieldRequest(Request $request, string $fieldName): bool
    {
        return in_array($fieldName, $this->getValidateNames($request));
    }

    /**
     * Set the document title in the response.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $title
     * @return void
     */
    public function setTitle(Response $response, string $title): void
    {
        $response->headers->set(self::TITLE_HEADER, Json::encode($title));
    }

    /**
     * Set the target selector in the response to override rendering.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $target
     * @return void
     */
    public function setTarget(Response $response, string $target): void
    {
        $response->headers->set(self::TARGET_RESPONSE_HEADER, $target);
    }

    /**
     * Accept the current overlay layer and optionally set a response value.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  mixed  $value
     * @return void
     */
    public function acceptLayer(Response $response, $value = null): void
    {
        $encoded = $value === null ? Json::encodeNull() : Json::encode($value);
        $response->headers->set(self::ACCEPT_LAYER_HEADER, $encoded);
    }

    /**
     * Dismiss the current overlay layer and optionally set a response value.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  mixed  $value
     * @return void
     */
    public function dismissLayer(Response $response, $value = null): void
    {
        $encoded = $value === null ? Json::encodeNull() : Json::encode($value);
        $response->headers->set(self::DISMISS_LAYER_HEADER, $encoded);
    }

    /**
     * Force the response to open a new overlay layer.
     *
     * This can be used to open a layer from the server even if the
     * original request was not targeting an overlay.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array  $options  Layer options (mode, target, etc.) as an associative array
     * @return void
     */
    public function openLayer(Response $response, array $options = []): void
    {
        $response->headers->set(self::OPEN_LAYER_HEADER, Json::encode($options));
    }

    /**
     * Update the layer context.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array  $context
     * @return void
     */
    public function setContext(Response $response, array $context): void
    {
        $response->headers->set(self::CONTEXT_RESPONSE_HEADER, Json::encode($context));
    }

    /**
     * Merge changes into the layer context.
     *
     * This is useful when you want to update specific context keys without
     * replacing the entire context. Set a key to null to remove it from context.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array  $changes
     * @return void
     */
    public function updateContext(Request $request, Response $response, array $changes): void
    {
        $currentContext = $this->getContext($request);

        foreach ($changes as $key => $value) {
            if ($value === null) {
                unset($currentContext[$key]);
            } else {
                $currentContext[$key] = $value;
            }
        }

        $this->setContext($response, $currentContext);
    }

    /**
     * Emit events to be triggered on the client.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  array  $events
     * @return void
     * @throws \InvalidArgumentException
     */
    public function emitEvents(Response $response, array $events): void
    {
        // Validate event structure
        foreach ($events as $event) {
            if (!is_array($event) || !isset($event['type'])) {
                throw new \InvalidArgumentException('Each event must be an array with a "type" property');
            }
        }

        $response->headers->set(self::EVENTS_HEADER, Json::encode($events));
    }

    /**
     * Emit a single event to be triggered on the client.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $type
     * @param  array  $props
     * @return void
     */
    public function emitEvent(Response $response, string $type, array $props = []): void
    {
        $event = array_merge(['type' => $type], $props);

        // Check if there are existing events
        $existingHeader = $response->headers->get(self::EVENTS_HEADER);
        $existingEvents = $existingHeader ? Json::decodeArray($existingHeader) ?? [] : [];

        $existingEvents[] = $event;

        $this->emitEvents($response, $existingEvents);
    }

    /**
     * Evict cached responses matching the given pattern.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $pattern
     * @return void
     */
    public function evictCache(Response $response, string $pattern): void
    {
        $response->headers->set(self::EVICT_CACHE_HEADER, $pattern);
    }

    /**
     * Expire cached responses matching the given pattern.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string|false  $pattern
     * @return void
     */
    public function expireCache(Response $response, $pattern): void
    {
        if ($pattern === false) {
            $response->headers->set(self::EXPIRE_CACHE_HEADER, 'false');
        } else {
            $response->headers->set(self::EXPIRE_CACHE_HEADER, $pattern);
        }
    }

    /**
     * Clear all cached responses.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function clearCache(Response $response): void
    {
        $this->evictCache($response, '*');
    }

    /**
     * Set the Last-Modified header for cache revalidation.
     *
     * This tells Unpoly when the content was last modified, enabling cache
     * revalidation with If-Modified-Since. This is part of standard HTTP
     * cache revalidation introduced in Unpoly 3.x.
     *
     * @see https://unpoly.com/Last-Modified
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  \DateTimeInterface  $time
     * @return void
     */
    public function setLastModified(Response $response, \DateTimeInterface $time): void
    {
        // Format as RFC 1123: "Wed, 15 Nov 2000 13:11:22 GMT"
        $formatted = gmdate('D, d M Y H:i:s', $time->getTimestamp()) . ' GMT';
        $response->headers->set('Last-Modified', $formatted);
    }

    /**
     * Set the ETag header for cache revalidation.
     *
     * This provides a hash/identifier for response content, enabling cache
     * revalidation with If-None-Match. This is part of standard HTTP cache
     * revalidation introduced in Unpoly 3.x.
     *
     * If the provided ETag is not properly quoted, this method will add quotes.
     * Weak ETags should start with 'W/' (e.g., 'W/"abc123"').
     *
     * @see https://unpoly.com/ETag
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $etag
     * @return void
     */
    public function setETag(Response $response, string $etag): void
    {
        // Ensure proper ETag format (quoted)
        if (!str_starts_with($etag, '"') && !str_starts_with($etag, 'W/"')) {
            $etag = '"' . $etag . '"';
        }

        $response->headers->set('ETag', $etag);
    }

    /**
     * Generate an ETag from content.
     *
     * This creates a hash of the content that can be used for cache revalidation.
     * By default, this generates a weak ETag (prefixed with 'W/').
     *
     * Weak ETags indicate semantic equivalence rather than byte-for-byte identity.
     * They are suitable for most use cases and are the recommended default.
     *
     * @param  string  $content
     * @param  bool  $weak  Whether to generate a weak ETag (default: true)
     * @return string
     */
    public function generateETag(string $content, bool $weak = true): string
    {
        $hash = md5($content);

        return $weak ? 'W/"' . $hash . '"' : '"' . $hash . '"';
    }

    /**
     * Set the response to 304 Not Modified status.
     *
     * This should be called when content has not changed since the client's
     * last request, as determined by comparing If-Modified-Since or If-None-Match
     * headers. The response body is cleared, but cache-related headers like
     * ETag, Last-Modified, and Vary are preserved.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return void
     */
    public function notModified(Response $response): void
    {
        $response->setStatusCode(304);
        $response->setContent('');
    }

    /**
     * Check if a response has 304 Not Modified status.
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @return bool
     */
    public function isNotModified(Response $response): bool
    {
        return $response->getStatusCode() === 304;
    }

    /**
     * Check if content should be revalidated based on request headers.
     *
     * This helper method compares the client's If-None-Match and If-Modified-Since
     * headers against the current ETag and last modified time. Returns true if the
     * content has changed and should be sent, false if a 304 Not Modified response
     * can be returned.
     *
     * If-None-Match (ETag) takes precedence over If-Modified-Since when both are present.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @param  \DateTimeInterface|null  $lastModified
     * @param  string|null  $etag
     * @return bool  True if content should be sent (changed), false if 304 can be returned
     */
    public function shouldRevalidate(Request $request, ?\DateTimeInterface $lastModified = null, ?string $etag = null): bool
    {
        // Check If-None-Match (ETag takes precedence)
        $ifNoneMatch = $this->getIfNoneMatch($request);
        if ($ifNoneMatch !== null && $etag !== null) {
            // Ensure etag is properly formatted for comparison
            $normalizedETag = $etag;
            if (!str_starts_with($etag, '"') && !str_starts_with($etag, 'W/"')) {
                $normalizedETag = '"' . $etag . '"';
            }

            // If ETags match, content has NOT changed (return false)
            if ($ifNoneMatch === $normalizedETag) {
                return false;
            }
        }

        // Check If-Modified-Since
        $ifModifiedSince = $this->getIfModifiedSince($request);
        if ($ifModifiedSince !== null && $lastModified !== null) {
            // If content was modified after the client's timestamp, it has changed (return true)
            // If content was NOT modified after the client's timestamp, it has NOT changed (return false)
            return $lastModified->getTimestamp() > $ifModifiedSince->getTimestamp();
        }

        // No conditional headers or no comparison values - should render fresh content
        return true;
    }

    /**
     * Add a header name to the Vary response header.
     *
     * The Vary header tells Unpoly which request headers influenced the response,
     * enabling proper cache partitioning. This is part of cache management in
     * Unpoly 3.x.
     *
     * This method ensures the header name is only added once and properly formats
     * the Vary header as a comma-separated list.
     *
     * @see https://unpoly.com/Vary
     *
     * @param  \Symfony\Component\HttpFoundation\Response  $response
     * @param  string  $headerName
     * @return void
     */
    public function addVary(Response $response, string $headerName): void
    {
        $vary = $response->headers->get('Vary', '');

        // Parse existing Vary header into array
        $headers = array_filter(array_map('trim', explode(',', $vary)));

        // Add new header if not already present
        if (!in_array($headerName, $headers, true)) {
            $headers[] = $headerName;
        }

        // Set the updated Vary header
        $response->headers->set('Vary', implode(', ', $headers));
    }
}
