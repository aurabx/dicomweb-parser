<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\DateTime;
use PHPUnit\Framework\TestCase;

final class DateTimeTest extends TestCase
{
    public function testReturnsArrayOfStrings(): void
    {
        $element = [
            'Value' => ['20250417123045.123456&+1000', '19991231235959.000000&-0500']
        ];

        $result = DateTime::parse($element);

        $this->assertSame([
            '20250417123045.123456&+1000',
            '19991231235959.000000&-0500'
        ], $result);
    }

    public function testCastsNonStringValues(): void
    {
        $element = ['Value' => [20250417123045, false, null]];

        $result = DateTime::parse($element);

        $this->assertSame(['20250417123045', '', ''], $result);
    }

    public function testReturnsNullWhenValueIsMissing(): void
    {
        $element = [];

        $result = DateTime::parse($element);

        $this->assertEmpty($result);
    }

    public function testReturnsNullWhenValueIsNull(): void
    {
        $element = ['Value' => null];

        $result = DateTime::parse($element);

        $this->assertEmpty($result);
    }

    public function testHandlesEmptyArray(): void
    {
        $element = ['Value' => []];

        $result = DateTime::parse($element);

        $this->assertSame([], $result);
    }
}
