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
    const CONTEXT_HEADER = 'X-Up-Context';
    const FAIL_CONTEXT_HEADER = 'X-Up-Fail-Context';
    const VALIDATE_HEADER = 'X-Up-Validate';

    // Unpoly 2.x Response Headers
    const TITLE_HEADER = 'X-Up-Title';
    const EVENTS_HEADER = 'X-Up-Events';
    const ACCEPT_LAYER_HEADER = 'X-Up-Accept-Layer';
    const DISMISS_LAYER_HEADER = 'X-Up-Dismiss-Layer';
    const CONTEXT_RESPONSE_HEADER = 'X-Up-Context';
    const TARGET_RESPONSE_HEADER = 'X-Up-Target';
    const EVICT_CACHE_HEADER = 'X-Up-Evict-Cache';
    const EXPIRE_CACHE_HEADER = 'X-Up-Expire-Cache';

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
            self::LOCATION_RESPONSE_HEADER => $request->getUri(),
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
    public function isTarget(Request $request, string $selector): bool
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
     * Check if the request is for the root layer.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isRoot(Request $request): bool
    {
        return $this->getMode($request) === 'root';
    }

    /**
     * Check if the request is for an overlay layer.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isOverlay(Request $request): bool
    {
        return !$this->isRoot($request);
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
     * Check if the request is a validation request.
     *
     * @param  \Symfony\Component\HttpFoundation\Request  $request
     * @return bool
     */
    public function isValidate(Request $request): bool
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
    public function isValidatingField(Request $request, string $fieldName): bool
    {
        return in_array($fieldName, $this->getValidateNames($request));
    }
}
