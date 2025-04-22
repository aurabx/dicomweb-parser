<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\Elements;

use Aurabx\DicomWebParser\Elements\PersonNameParser;
use PHPUnit\Framework\TestCase;

final class PersonNameParserTest extends TestCase
{
    public function testParsesRawDicomStringName(): void
    {
        $element = ['Value' => ['Doe^John^Quincy^Dr^Jr']];

        $result = PersonNameParser::parse($element);

        $this->assertSame([[
            'Alphabetic' => [
                'FamilyName' => 'Doe',
                'GivenName' => 'John',
                'MiddleName' => 'Quincy',
                'NamePrefix' => 'Dr',
                'NameSuffix' => 'Jr',
            ]
        ]], $result);
    }

    public function testParsesPartialDicomStringName(): void
    {
        $element = ['Value' => ['Smith^Jane']];

        $result = PersonNameParser::parse($element);

        $this->assertSame([[
            'Alphabetic' => [
                'FamilyName' => 'Smith',
                'GivenName' => 'Jane',
                'MiddleName' => null,
                'NamePrefix' => null,
                'NameSuffix' => null,
            ]
        ]], $result);
    }

    public function testPreservesStructuredNameData(): void
    {
        $structured = [
            'Alphabetic' => [
                'FamilyName' => 'Doe',
                'GivenName' => 'John',
            ]
        ];

        $element = ['Value' => [$structured]];

        $result = PersonNameParser::parse($element);

        $this->assertSame([$structured], $result);
    }

    public function testHandlesMixedStructuredAndStrings(): void
    {
        $element = [
            'Value' => [
                'Tanaka^Ichiro',
                [
                    'Alphabetic' => [
                        'FamilyName' => 'Yamada',
                        'GivenName' => 'Taro'
                    ]
                ]
            ]
        ];

        $result = PersonNameParser::parse($element);

        $this->assertSame([
            [
                'Alphabetic' => [
                    'FamilyName' => 'Tanaka',
                    'GivenName' => 'Ichiro',
                    'MiddleName' => null,
                    'NamePrefix' => null,
                    'NameSuffix' => null,
                ]
            ],
            [
                'Alphabetic' => [
                    'FamilyName' => 'Yamada',
                    'GivenName' => 'Taro',
                ]
            ]
        ], $result);
    }

    public function testReturnsNullIfValueMissing(): void
    {
        $this->assertEmpty(PersonNameParser::parse([]));
    }

    public function testReturnsNullIfValueIsNull(): void
    {
        $this->assertEmpty(PersonNameParser::parse(['Value' => null]));
    }

    public function testReturnsEmptyArrayIfValueIsEmpty(): void
    {
        $this->assertSame([], PersonNameParser::parse(['Value' => []]));
    }
}
