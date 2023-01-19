<?php

namespace Webstronauts\Unpoly;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Unpoly
{
    /**
     * Response header to echo request's URL.
     */
    const LOCATION_RESPONSE_HEADER = 'X-Up-Location';

    /**
     * Response header to echo request's method.
     */
    const METHOD_RESPONSE_HEADER = 'X-Up-Method';

    /**
     * Cookie name to echo request's method.
     */
    const METHOD_COOKIE_NAME = '_up_method';

    /**
     * @see Webstronauts\Unpoly\Unpoly::isUnpolyRequest()
     */
    public static function isUpRequest(Request $request): bool
    {
        return static::isUnpolyRequest($request);
    }

    /**
     * Returns whether the current request is a [page fragment update](https://unpoly.com/up.replace)
     * triggered by an Unpoly frontend.
     *
     * This will eventually just check for the `X-Up-Version header`.
     * Just in case a user still has an older version of Unpoly running on the frontend,
     * we also check for the X-Up-Target header.
     */
    public static function isUnpolyRequest(Request $request): bool
    {
        return static::getVersion($request) !== null || static::getTarget($request) !== null;
    }

    /**
     * Returns the current Unpoly version.
     *
     * The version is guaranteed to be set for all Unpoly requests.
     */
    public static function getVersion(Request $request): ?string
    {
        return $request->headers->get('X-Up-Version');
    }

    /**
     * Returns the mode of the targeted layer.
     *
     * Server-side code is free to render different HTML for different modes.
     * For example, you might prefer to not render a site navigation for overlays.
     */
    public static function getMode(Request $request): ?string
    {
        return $request->headers->get('X-Up-Mode');
    }

    /**
     * Returns the mode of the layer targeted for a failed fragment update.
     *
     * A fragment update is considered failed if the server responds with
     * a status code other than 2xx, but still renders HTML.
     * Server-side code is free to render different HTML for different modes.
     * For example, you might prefer to not render a site navigation for overlays.
     */
    public static function getFailMode(Request $request): ?string
    {
        return $request->headers->get('X-Up-Fail-Mode');
    }

    /**
     * Returns the CSS selector for a fragment that Unpoly will update in
     * case of a successful response (200 status code).
     *
     * The Unpoly frontend will expect an HTML response containing an element
     * that matches this selector.
     *
     * Server-side code is free to optimize its successful response by only returning HTML
     * that matches this selector.
     */
    public static function getTarget(Request $request): ?string
    {
        return $request->headers->get('X-Up-Target');
    }

    /**
     * Returns the CSS selector for a fragment that Unpoly will update in
     * case of an failed response. Server errors or validation failures are
     * all examples for a failed response (non-200 status code).
     *
     * The Unpoly frontend will expect an HTML response containing an element
     * that matches this selector.
     *
     * Server-side code is free to optimize its response by only returning HTML
     * that matches this selector.
     */
    public static function getFailTarget(Request $request): ?string
    {
        return $request->headers->get('X-Up-Fail-Target');
    }

    /**
     * Returns whether the given CSS selector is targeted by the current fragment
     * update in case of a successful response (200 status code).
     *
     * Note that the matching logic is very simplistic and does not actually know
     * how your page layout is structured. It will return `true` if
     * the tested selector and the requested CSS selector matches exactly, or if the
     * requested selector is `body` or `html`.
     *
     * Always returns `true` if the current request is not an Unpoly fragment update.
     */
    public static function isTarget(Request $request, string $target): bool
    {
       return static::queryTarget(static::getTarget($request), $target);
    }

    /**
     * Returns whether the given CSS selector is targeted by the current fragment
     * update in case of a failed response (non-200 status code).
     *
     * Note that the matching logic is very simplistic and does not actually know
     * how your page layout is structured. It will return `true` if
     * the tested selector and the requested CSS selector matches exactly, or if the
     * requested selector is `body` or `html`.
     *
     * Always returns `true` if the current request is not an Unpoly fragment update.
     */
    public static function isFailTarget(Request $request, string $target): bool
    {
        return static::queryTarget(static::getFailTarget($request), $target);
    }

    /**
     * Returns whether the given CSS selector is targeted by the current fragment
     * update for either a success or a failed response.
     *
     * Note that the matching logic is very simplistic and does not actually know
     * how your page layout is structured. It will return `true` if
     * the tested selector and the requested CSS selector matches exactly, or if the
     * requested selector is `body` or `html`.
     *
     * Always returns `true` if the current request is not an Unpoly fragment update.
     */
    public static function isAnyTarget(Request $request, string $target): bool
    {
        return static::isTarget($request, $target) || static::isFailTarget($request, $target);
    }

    /**
     * Returns whether the current form submission should be
     * [validated](https://unpoly.com/input-up-validate) (and not be saved to the database).
     */
    public static function isValidationRequest(Request $request): bool
    {
        return static::getValidateNames($request) !== null;
    }

    /**
     * If the current form submission is a [validation](https://unpoly.com/input-up-validate),
     * this returns the name attributes of the form field that has triggered
     * the validation.
     *
     * Note that multiple validating form fields may be batched into a single request.
     */
    public static function getValidateNames(Request $request): ?string
    {
        return $request->headers->get('X-Up-Validate');
    }

    protected static function queryTarget(string $actualTarget, string $testedTarget): bool
    {
        if (! static::isUnpolyRequest($request)) {
            return true;
        }

        if ($actualTarget === $testedTarget) {
            return true;
        }

        if ($actualTarget === 'html') {
            return true;
        }

        if ($actualTarget === 'body' && ! in_array($testedTarget, ['head', 'title', 'meta'])) {
            return true;
        }

        return false;
    }

    /**
     * Modifies the HTTP headers and cookies of the response so that it can be
     * properly handled by the Unpoly javascript.
     */
    public function decorateResponse(Request $request, Response $response): void
    {
        $this->echoRequestHeaders($request, $response);
        $this->appendMethodCookie($request, $response);
    }

    /**
     * Unpoly requires these headers to detect redirects,
     * which are otherwise undetectable for an AJAX client.
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
     */
    protected function appendMethodCookie(Request $request, Response $response): void
    {
        if (! $request->isMethod('GET') && ! static::isUpRequest($request)) {
            $response->headers->setCookie(new Cookie(self::METHOD_COOKIE_NAME, $request->getMethod()));
        } else {
            $response->headers->removeCookie(self::METHOD_COOKIE_NAME);
        }
    }
}
