<?php

namespace Webstronauts\Unpoly\Tests\Support;

use PHPUnit\Framework\TestCase;
use Webstronauts\Unpoly\Support\Json;

class JsonTest extends TestCase
{
    public function test_encode_simple_string()
    {
        $this->assertEquals('"hello"', Json::encode('hello'));
    }

    public function test_encode_array()
    {
        $this->assertEquals('[1,2,3]', Json::encode([1, 2, 3]));
    }

    public function test_encode_object()
    {
        $this->assertEquals('{"name":"John","age":30}', Json::encode(['name' => 'John', 'age' => 30]));
    }

    public function test_encode_null()
    {
        $this->assertEquals('null', Json::encode(null));
    }

    public function test_encode_special_characters()
    {
        // JSON_HEX_TAG escapes < and >
        $this->assertEquals('"\u003Cscript\u003E"', Json::encode('<script>'));

        // JSON_HEX_AMP escapes &
        $this->assertEquals('"\u0026"', Json::encode('&'));

        // JSON_HEX_APOS escapes '
        $this->assertEquals('"\u0027"', Json::encode("'"));

        // JSON_HEX_QUOT escapes "
        $this->assertEquals('"\u0022"', Json::encode('"'));
    }

    public function test_encode_slashes()
    {
        // JSON_UNESCAPED_SLASHES keeps / unescaped
        $this->assertEquals('"/path/to/resource"', Json::encode('/path/to/resource'));
    }

    public function test_encode_unicode()
    {
        // High ASCII characters are escaped for Unpoly 3.x compatibility
        $this->assertEquals('"caf\u00e9"', Json::encode('café'));
    }

    public function test_decode_valid_json()
    {
        $this->assertEquals('hello', Json::decode('"hello"'));
        $this->assertEquals([1, 2, 3], Json::decode('[1,2,3]'));
        $this->assertEquals(['name' => 'John'], Json::decode('{"name":"John"}'));
    }

    public function test_decode_null()
    {
        $this->assertNull(Json::decode('null'));
    }

    public function test_decode_empty_string()
    {
        $this->assertNull(Json::decode(''));
    }

    public function test_decode_null_value()
    {
        $this->assertNull(Json::decode(null));
    }

    public function test_decode_malformed_json()
    {
        $this->assertNull(Json::decode('{invalid json}'));
        $this->assertNull(Json::decode('not json at all'));
    }

    public function test_decode_array_with_valid_array()
    {
        $this->assertEquals([1, 2, 3], Json::decodeArray('[1,2,3]'));
    }

    public function test_decode_array_with_empty_array()
    {
        $this->assertEquals([], Json::decodeArray('[]'));
    }

    public function test_decode_array_with_object()
    {
        // Objects are returned as associative arrays in PHP
        $this->assertEquals(['name' => 'John'], Json::decodeArray('{"name":"John"}'));
    }

    public function test_decode_array_with_non_array()
    {
        $this->assertNull(Json::decodeArray('"string"'));
        $this->assertNull(Json::decodeArray('123'));
        $this->assertNull(Json::decodeArray('null'));
    }

    public function test_decode_array_with_null()
    {
        $this->assertNull(Json::decodeArray(null));
    }

    public function test_decode_object_with_valid_object()
    {
        $this->assertEquals(['name' => 'John', 'age' => 30], Json::decodeObject('{"name":"John","age":30}'));
    }

    public function test_decode_object_with_empty_object()
    {
        $this->assertEquals([], Json::decodeObject('{}'));
    }

    public function test_decode_object_with_array()
    {
        // Indexed arrays should return null
        $this->assertNull(Json::decodeObject('[1,2,3]'));
    }

    public function test_decode_object_with_non_object()
    {
        $this->assertNull(Json::decodeObject('"string"'));
        $this->assertNull(Json::decodeObject('123'));
        $this->assertNull(Json::decodeObject('null'));
    }

    public function test_decode_object_with_null()
    {
        $this->assertNull(Json::decodeObject(null));
    }

    public function test_is_null_with_null()
    {
        $this->assertTrue(Json::isNull(null));
    }

    public function test_is_null_with_string_null()
    {
        $this->assertTrue(Json::isNull('null'));
    }

    public function test_is_null_with_other_values()
    {
        $this->assertFalse(Json::isNull(''));
        $this->assertFalse(Json::isNull('0'));
        $this->assertFalse(Json::isNull(0));
        $this->assertFalse(Json::isNull(false));
        $this->assertFalse(Json::isNull('string'));
    }

    public function test_encode_null_returns_string_null()
    {
        $this->assertEquals('null', Json::encodeNull());
    }

    public function test_decode_relaxed_json_with_unquoted_keys()
    {
        // Standard JSON decoder can't handle unquoted keys
        // This would require a more sophisticated parser
        $this->assertNull(Json::decode('{name: "John"}'));
    }

    public function test_encode_with_nested_structures()
    {
        $data = [
            'user' => [
                'name' => 'John',
                'tags' => ['admin', 'user'],
            ],
        ];

        $expected = '{"user":{"name":"John","tags":["admin","user"]}}';
        $this->assertEquals($expected, Json::encode($data));
    }

    public function test_decode_with_nested_structures()
    {
        $json = '{"user":{"name":"John","tags":["admin","user"]}}';

        $expected = [
            'user' => [
                'name' => 'John',
                'tags' => ['admin', 'user'],
            ],
        ];

        $this->assertEquals($expected, Json::decode($json));
    }
}
