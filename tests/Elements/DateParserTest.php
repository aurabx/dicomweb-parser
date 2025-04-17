<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Elements;

use Aurabx\DicomWebParser\Elements\DateParser;
use PHPUnit\Framework\TestCase;

final class DateParserTest extends TestCase
{
    public function testReturnsArrayOfStrings(): void
    {
        $element = ['Value' => ['20250417', '19991231']];

        $result = DateParser::parse($element);

        $this->assertSame(['20250417', '19991231'], $result);
    }

    public function testCastsNonStringValuesToStrings(): void
    {
        $element = ['Value' => [20250417, 19991231]];

        $result = DateParser::parse($element);

        $this->assertSame(['20250417', '19991231'], $result);
    }

    public function testReturnsEmptyArrayWhenValueIsEmpty(): void
    {
        $element = ['Value' => []];

        $result = DateParser::parse($element);

        $this->assertSame([], $result);
    }

    public function testReturnsNullWhenValueIsMissing(): void
    {
        $element = [];

        $result = DateParser::parse($element);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenValueIsNull(): void
    {
        $element = ['Value' => null];

        $result = DateParser::parse($element);

        $this->assertNull($result);
    }

    public function testHandlesMixedTypes(): void
    {
        $element = ['Value' => ['20250417', 20250418, false, null]];

        $result = DateParser::parse($element);

        $this->assertSame(['20250417', '20250418', '', ''], $result);
    }
}
