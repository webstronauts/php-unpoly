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
}
