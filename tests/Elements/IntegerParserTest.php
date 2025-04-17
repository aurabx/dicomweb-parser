<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Elements;

use Aurabx\DicomWebParser\Elements\IntegerParser;
use PHPUnit\Framework\TestCase;

final class IntegerParserTest extends TestCase
{
    public function testReturnsArrayOfStrings(): void
    {
        $element = ['Value' => ['42', '-17', '0']];

        $result = IntegerParser::parse($element);

        $this->assertSame(['42', '-17', '0'], $result);
    }

    public function testCastsNonStringsToStrings(): void
    {
        $element = ['Value' => [42, -17, true, null]];

        $result = IntegerParser::parse($element);

        $this->assertSame(['42', '-17', '1', ''], $result);
    }

    public function testReturnsNullWhenValueIsMissing(): void
    {
        $element = [];

        $result = IntegerParser::parse($element);

        $this->assertNull($result);
    }

    public function testReturnsNullWhenValueIsNull(): void
    {
        $element = ['Value' => null];

        $result = IntegerParser::parse($element);

        $this->assertNull($result);
    }

    public function testReturnsEmptyArrayWhenValueIsEmpty(): void
    {
        $element = ['Value' => []];

        $result = IntegerParser::parse($element);

        $this->assertSame([], $result);
    }
}
