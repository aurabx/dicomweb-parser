<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\FloatingPointParser;
use PHPUnit\Framework\TestCase;

final class FloatingPointParserTest extends TestCase
{
    public function testReturnsStringValues(): void
    {
        $element = ['Value' => ['1.23', '-4.56', '0.0']];

        $result = FloatingPointParser::parse($element);

        $this->assertSame(['1.23', '-4.56', '0.0'], $result);
    }

    public function testReturnsEmptyArrayIfValueIsEmpty(): void
    {
        $element = ['Value' => []];

        $result = FloatingPointParser::parse($element);

        $this->assertSame([], $result);
    }

    public function testReturnsNullIfValueIsMissing(): void
    {
        $element = [];

        $result = FloatingPointParser::parse($element);

        $this->assertNull($result[0]);
    }

    public function testReturnsNullIfValueIsExplicitlyNull(): void
    {
        $element = ['Value' => null];

        $result = FloatingPointParser::parse($element);

        $this->assertNull($result[0]);
    }

    public function testCastsNonStringsToStrings(): void
    {
        $element = ['Value' => [1.0, 2, false, null]];

        $result = FloatingPointParser::parse($element);

        $this->assertSame(['1', '2', '', ''], $result);
    }
}
