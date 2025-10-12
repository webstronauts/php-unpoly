<?php

namespace Webstronauts\Unpoly\Tests\Support;

use PHPUnit\Framework\TestCase;
use Webstronauts\Unpoly\Support\Json;

class JsonTest extends TestCase
{
    public function testEncodeSimpleString()
    {
        $this->assertEquals('"hello"', Json::encode('hello'));
    }

    public function testEncodeArray()
    {
        $this->assertEquals('[1,2,3]', Json::encode([1, 2, 3]));
    }

    public function testEncodeObject()
    {
        $this->assertEquals('{"name":"John","age":30}', Json::encode(['name' => 'John', 'age' => 30]));
    }

    public function testEncodeNull()
    {
        $this->assertEquals('null', Json::encode(null));
    }

    public function testEncodeSpecialCharacters()
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

    public function testEncodeSlashes()
    {
        // JSON_UNESCAPED_SLASHES keeps / unescaped
        $this->assertEquals('"/path/to/resource"', Json::encode('/path/to/resource'));
    }

    public function testEncodeUnicode()
    {
        // High ASCII characters are escaped for Unpoly 3.x compatibility
        $this->assertEquals('"caf\u00e9"', Json::encode('café'));
    }

    public function testDecodeValidJson()
    {
        $this->assertEquals('hello', Json::decode('"hello"'));
        $this->assertEquals([1, 2, 3], Json::decode('[1,2,3]'));
        $this->assertEquals(['name' => 'John'], Json::decode('{"name":"John"}'));
    }

    public function testDecodeNull()
    {
        $this->assertNull(Json::decode('null'));
    }

    public function testDecodeEmptyString()
    {
        $this->assertNull(Json::decode(''));
    }

    public function testDecodeNullValue()
    {
        $this->assertNull(Json::decode(null));
    }

    public function testDecodeMalformedJson()
    {
        $this->assertNull(Json::decode('{invalid json}'));
        $this->assertNull(Json::decode('not json at all'));
    }

    public function testDecodeArrayWithValidArray()
    {
        $this->assertEquals([1, 2, 3], Json::decodeArray('[1,2,3]'));
    }

    public function testDecodeArrayWithEmptyArray()
    {
        $this->assertEquals([], Json::decodeArray('[]'));
    }

    public function testDecodeArrayWithObject()
    {
        // Objects are returned as associative arrays in PHP
        $this->assertEquals(['name' => 'John'], Json::decodeArray('{"name":"John"}'));
    }

    public function testDecodeArrayWithNonArray()
    {
        $this->assertNull(Json::decodeArray('"string"'));
        $this->assertNull(Json::decodeArray('123'));
        $this->assertNull(Json::decodeArray('null'));
    }

    public function testDecodeArrayWithNull()
    {
        $this->assertNull(Json::decodeArray(null));
    }

    public function testDecodeObjectWithValidObject()
    {
        $this->assertEquals(['name' => 'John', 'age' => 30], Json::decodeObject('{"name":"John","age":30}'));
    }

    public function testDecodeObjectWithEmptyObject()
    {
        $this->assertEquals([], Json::decodeObject('{}'));
    }

    public function testDecodeObjectWithArray()
    {
        // Indexed arrays should return null
        $this->assertNull(Json::decodeObject('[1,2,3]'));
    }

    public function testDecodeObjectWithNonObject()
    {
        $this->assertNull(Json::decodeObject('"string"'));
        $this->assertNull(Json::decodeObject('123'));
        $this->assertNull(Json::decodeObject('null'));
    }

    public function testDecodeObjectWithNull()
    {
        $this->assertNull(Json::decodeObject(null));
    }

    public function testIsNullWithNull()
    {
        $this->assertTrue(Json::isNull(null));
    }

    public function testIsNullWithStringNull()
    {
        $this->assertTrue(Json::isNull('null'));
    }

    public function testIsNullWithOtherValues()
    {
        $this->assertFalse(Json::isNull(''));
        $this->assertFalse(Json::isNull('0'));
        $this->assertFalse(Json::isNull(0));
        $this->assertFalse(Json::isNull(false));
        $this->assertFalse(Json::isNull('string'));
    }

    public function testEncodeNullReturnsStringNull()
    {
        $this->assertEquals('null', Json::encodeNull());
    }

    public function testDecodeRelaxedJsonWithUnquotedKeys()
    {
        // Standard JSON decoder can't handle unquoted keys
        // This would require a more sophisticated parser
        $this->assertNull(Json::decode('{name: "John"}'));
    }

    public function testEncodeWithNestedStructures()
    {
        $data = [
            'user' => [
                'name' => 'John',
                'tags' => ['admin', 'user']
            ]
        ];

        $expected = '{"user":{"name":"John","tags":["admin","user"]}}';
        $this->assertEquals($expected, Json::encode($data));
    }

    public function testDecodeWithNestedStructures()
    {
        $json = '{"user":{"name":"John","tags":["admin","user"]}}';

        $expected = [
            'user' => [
                'name' => 'John',
                'tags' => ['admin', 'user']
            ]
        ];

        $this->assertEquals($expected, Json::decode($json));
    }
}
