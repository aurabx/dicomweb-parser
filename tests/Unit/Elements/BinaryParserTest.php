<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\BinaryParser;
use PHPUnit\Framework\TestCase;

final class BinaryParserTest extends TestCase
{
    public function testParsesInlineBinary(): void
    {
        $binaryData = 'Hello World!';
        $base64 = base64_encode($binaryData);

        $element = [
            'InlineBinary' => $base64,
            'Value' => null,
        ];

        $result = BinaryParser::parse($element);

        $this->assertSame([$binaryData], $result);
    }

    public function testReturnsValueIfInlineBinaryIsNull(): void
    {
        $expected = ['some', 'binary', 'values'];

        $element = [
            'InlineBinary' => null,
            'Value' => $expected,
        ];

        $result = BinaryParser::parse($element);

        $this->assertSame($expected, $result);
    }

    public function testReturnsNullIfBothInlineBinaryAndValueAreNull(): void
    {
        $element = [
            'InlineBinary' => null,
            'Value' => null,
        ];

        $result = BinaryParser::parse($element);

        $this->assertEmpty($result);
    }
}
