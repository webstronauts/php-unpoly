<?php

namespace Webstronauts\Unpoly\Support;

class Json
{
    /**
     * Encode a value as JSON for Unpoly headers.
     *
     * Unpoly 3.x requires high ASCII characters to be escaped.
     * This method uses JSON encoding flags that ensure compatibility
     * with both Unpoly 2.x and 3.x.
     *
     * @param  mixed  $value
     * @return string
     */
    public static function encode($value): string
    {
        return json_encode(
            $value,
            JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    /**
     * Parse relaxed JSON from Unpoly request headers.
     *
     * Unpoly may send "relaxed JSON" in request headers, which is more
     * forgiving than strict JSON. This method attempts to decode JSON
     * and handles edge cases gracefully.
     *
     * @param  string|null  $json
     * @return mixed
     */
    public static function decode(?string $json)
    {
        if ($json === null || $json === '') {
            return null;
        }

        $decoded = json_decode($json, true);

        // If decoding failed but the string is not empty, return null
        if ($decoded === null && $json !== 'null') {
            return null;
        }

        return $decoded;
    }

    /**
     * Parse JSON array from Unpoly request header.
     *
     * Returns an array or null if parsing fails or value is not an array.
     *
     * @param  string|null  $json
     * @return array|null
     */
    public static function decodeArray(?string $json): ?array
    {
        $decoded = self::decode($json);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Parse JSON object (associative array) from Unpoly request header.
     *
     * Returns an associative array or null if parsing fails or value is not an object.
     *
     * @param  string|null  $json
     * @return array|null
     */
    public static function decodeObject(?string $json): ?array
    {
        $decoded = self::decode($json);

        // Check if it's an associative array (object)
        if (is_array($decoded) && (empty($decoded) || array_keys($decoded) !== range(0, count($decoded) - 1))) {
            return $decoded;
        }

        return null;
    }

    /**
     * Check if a value represents JSON null.
     *
     * Unpoly uses null values in various contexts (e.g., acceptLayer, dismissLayer).
     * This method distinguishes between null and the string "null".
     *
     * @param  mixed  $value
     * @return bool
     */
    public static function isNull($value): bool
    {
        return $value === null || $value === 'null';
    }

    /**
     * Encode null for Unpoly headers.
     *
     * Returns the string "null" to represent JSON null in headers.
     *
     * @return string
     */
    public static function encodeNull(): string
    {
        return 'null';
    }
}
