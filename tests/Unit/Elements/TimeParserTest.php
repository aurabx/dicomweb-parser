<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\TimeParser;
use PHPUnit\Framework\TestCase;

final class TimeParserTest extends TestCase
{
    public function testReturnsRawTimeStrings(): void
    {
        $element = ['Value' => ['120000', '120000.123456', '0930', '15']];

        $result = TimeParser::parse($element);

        $this->assertSame(['120000', '120000.123456', '0930', '15'], $result);
    }

    public function testCastsNonStringsToStrings(): void
    {
        $element = ['Value' => [120000, false, null]];

        $result = TimeParser::parse($element);

        $this->assertSame(['120000', '', ''], $result);
    }

    public function testReturnsEmptyArrayIfValueIsEmpty(): void
    {
        $element = ['Value' => []];

        $result = TimeParser::parse($element);

        $this->assertSame([], $result);
    }

    public function testReturnsNullIfValueMissing(): void
    {
        $element = [];

        $result = TimeParser::parse($element);

        $this->assertNull($result[0]);
    }

    public function testReturnsNullIfValueIsNull(): void
    {
        $element = ['Value' => null];

        $result = TimeParser::parse($element);

        $this->assertNull($result[0]);
    }
}
