<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Elements;

use Aurabx\DicomWebParser\Elements\AttributeTagParser;
use PHPUnit\Framework\TestCase;

final class AttributeTagParserTest extends TestCase
{
    public function testParsesStringTags(): void
    {
        $element = [
            'Value' => ['00100010', '0020000D'],
        ];

        // We'll stub the normalizeTag method
        $expected = ['00100010', '0020000D'];

        // Mocking the static method with runkit or patchwork is messy.
        // Better: assume normalizeTag works and test integration separately.
        // Or, test without mocking here and use real normalization logic.
        $result = AttributeTagParser::parse($element);

        $this->assertSame($expected, $result);
    }

    public function testParsesBinaryTags(): void
    {
        $element = [
            'Value' => [[0x0010, 0x0010], [0x0020, 0x000D]],
        ];

        $expected = ['00100010', '0020000D'];

        $result = AttributeTagParser::parse($element);

        $this->assertSame($expected, $result);
    }

    public function testPassesThroughUnexpectedFormats(): void
    {
        $element = [
            'Value' => [42, [1, 2, 3], null],
        ];

        $expected = [42, [1, 2, 3], null];

        $result = AttributeTagParser::parse($element);

        $this->assertSame($expected, $result);
    }
}
