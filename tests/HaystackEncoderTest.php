<?php

use Cxalloy\Haystack\HaystackEncoder;
use PHPUnit\Framework\TestCase;

class HaystackEncoderTest extends TestCase
{
    public function testEncodeSimpleArray()
    {
        $data = ['temp' => 22.5, 'status' => "ok"];
        $expected = '{temp:22.5,status:"ok"}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertIsString($encodedData);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeNestedArray()
    {
        $data = ['metadata' => ['manufacturer' => "Acme", 'year' => 2023]];
        $expected = '{metadata:{manufacturer:"Acme",year:2023}}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertIsString($encodedData);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeDateTime()
    {
        $data = ['updated' => new \DateTime("2023-03-28T14:56:00Z")];
        $expected = '{updated:ts:2023-03-28T14:56:00Z}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertIsString($encodedData);
        $this->assertSame($expected, $encodedData);
    }

    public function testDecodeFromHaystackFormat()
    {
        $jsonString = '{"test":"value"}';
        $decodedData = HaystackEncoder::decodeFromHaystackFormat($jsonString);
        $this->assertIsArray($decodedData);
        $this->assertSame(['test' => 'value'], $decodedData);
    }

    public function testEncodeToHaystackFormatThrowsExceptionOnFailure()
    {
        $this->expectException(Exception::class);
        HaystackEncoder::encodeToHaystackFormat("\xB1\x31");
    }

    public function testDecodeFromHaystackFormatThrowsExceptionOnFailure()
    {
        $this->expectException(Exception::class);
        HaystackEncoder::decodeFromHaystackFormat("invalid json");
    }

    public function testEncodeBooleans()
    {
        $data = ['active' => true, 'maintenance' => false];
        $expected = '{active:T,maintenance:F}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeNull()
    {
        $data = ['value' => null];
        $expected = '{value:Z}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeEmptyArray()
    {
        $data = ['list' => [], 'dict' => new stdClass()];
        $expected = '{list:[],dict:{}}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeSpecialCharacters()
    {
        $data = ['text' => "Line 1\nLine 2\rLine 3\tTab"];
        $expected = '{text:"Line 1\\nLine 2\\rLine 3\\tTab"}';
        $encodedData = HaystackEncoder::encodeToHaystackFormat($data);
        $this->assertSame($expected, $encodedData);
    }

    public function testEncodeUnsupportedType()
    {
        $this->expectException(\InvalidArgumentException::class);
        HaystackEncoder::encodeToHaystackFormat(['unsupported' => curl_init()]);
    }
}
