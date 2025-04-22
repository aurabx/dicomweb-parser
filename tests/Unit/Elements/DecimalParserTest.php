<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\DecimalParser;
use PHPUnit\Framework\TestCase;

final class DecimalParserTest extends TestCase
{
    public function testReturnsDecimalStrings(): void
    {
        $element = ['Value' => ['123.456', '-0.001', '42']];

        $result = DecimalParser::parse($element);

        $this->assertSame(['123.456', '-0.001', '42'], $result);
    }

    public function testCastsNonStringsToStrings(): void
    {
        $element = ['Value' => [123.456, 42, true, null]];

        $result = DecimalParser::parse($element);

        $this->assertSame(['123.456', '42', '1', ''], $result);
    }

    public function testHandlesEmptyValue(): void
    {
        $element = ['Value' => []];

        $result = DecimalParser::parse($element);

        $this->assertSame([], $result);
    }

    public function testReturnsNullIfValueMissing(): void
    {
        $result = DecimalParser::parse([]);

        $this->assertNull($result[0]);
    }

    public function testReturnsNullIfValueIsNull(): void
    {
        $element = ['Value' => null];

        $result = DecimalParser::parse($element);

        $this->assertNull($result[0]);
    }
}
