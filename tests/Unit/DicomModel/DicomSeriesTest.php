<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\DicomModel;

use Aurabx\DicomWebParser\DicomModel\DicomElement;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\ParserException;
use Aurabx\DicomWebParser\ParserOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DicomSeriesTest extends TestCase
{
    #[Test]
    public function it_can_be_instantiated_with_explicit_uid(): void
    {
        $series = new DicomSeries(
            instances: [],
            seriesInstanceUid: '1.2.3.4.5',
        );

        $this->assertSame('1.2.3.4.5', $series->getSeriesInstanceUid());
    }

    #[Test]
    public function it_can_be_instantiated_with_instance_uid(): void
    {
        $instance = $this->mockInstance([
            '0020000E' => '9.9.9.9'
        ]);

        $series = new DicomSeries(
            instances: [$instance],
        );

        $this->assertSame('9.9.9.9', $series->getSeriesInstanceUid());
    }

    #[Test]
    public function it_throws_if_no_uid_can_be_determined(): void
    {
        $this->expectException(ParserException::class);
        new DicomSeries(instances: []);
    }

    #[Test]
    public function it_returns_expected_metadata(): void
    {
        $instance = $this->mockInstance([
            '00080060' => 'MR',
            '00200011' => 7,
            '0008103E' => 'Test Desc',
            '0020000E' => 'UID-XYZ'
        ]);

        $series = new DicomSeries(
            instances: [$instance],
        );

        $this->assertSame('MR', $series->getModality());
        $this->assertSame(7, $series->getSeriesNumber());
        $this->assertSame('Test Desc', $series->getSeriesDescription());
        $this->assertSame(1, $series->getInstanceCount());
    }

    #[Test]
    public function it_adds_instance_with_matching_uid(): void
    {
        $instance = $this->mockInstance([
            '0020000E' => 'MatchUID',
        ]);
        $instance->method('getElementFirstValueByKeyword')->willReturn('MatchUID');

        $series = new DicomSeries(
            instances: [$instance],
        );

        $newInstance = clone $instance;
        $series->addInstance($newInstance);

        $this->assertCount(2, $series->getInstances());
    }

    #[Test]
    public function it_rejects_instance_with_mismatched_uid(): void
    {
        $instance = $this->mockInstance(['0020000E' => 'SeriesA']);
        $instance->method('getElementFirstValueByKeyword')->willReturn('SeriesA');

        $series = new DicomSeries(
            instances: [$instance],
        );

        $mismatch = $this->mockInstance(['0020000E' => 'SeriesB']);
        $mismatch->method('getElementFirstValueByKeyword')->willReturn('SeriesB');

        $this->expectException(ParserException::class);
        $series->addInstance($mismatch);
    }

    #[Test]
    public function it_sorts_instances_by_instance_number(): void
    {
        $inst1 = $this->mockInstance(['00200013' => 20]);
        $inst2 = $this->mockInstance(['00200013' => 5]);
        $inst3 = $this->mockInstance(['00200013' => 10]);

        $series = new DicomSeries([$inst1, $inst2, $inst3], '1.2.3.4.5');
        $series->sortInstancesByNumber();

        $sorted = $series->getInstances();
        $this->assertSame(5, $sorted[0]->getElementFirstValue('00200013'));
        $this->assertSame(10, $sorted[1]->getElementFirstValue('00200013'));
        $this->assertSame(20, $sorted[2]->getElementFirstValue('00200013'));
    }

    #[Test]
    public function it_exports_named_array(): void
    {
        $element = new DicomElement('00080060', 'CS', 'CT');
        $instance = new DicomInstance();
        $instance->addElement('00080060', $element);

        // Build the DicomSeries
        $series = new DicomSeries([$instance], '1.2.3.4.5');
        $result = $series->toArray(ParserOptions::USE_KEYWORDS);

        // Assert expected outcome
        $this->assertSame(['Modality' => 'CT'], $result);
    }


    private function mockInstance(array $values): \PHPUnit\Framework\MockObject\MockObject
    {
        $mock = $this->createMock(DicomInstance::class);
        $mock->method('hasElement')->willReturnCallback(fn($tag) => array_key_exists($tag, $values));
        $mock->method('getElementFirstValue')->willReturnCallback(fn($tag) => $values[$tag] ?? null);
        $mock->method('getElement')->willReturnCallback(fn($tag) => new class($values, $tag) {
            private array $values;
            private string $tag;
            public function __construct(array $values, string $tag)
            {
                $this->values = $values;
                $this->tag = $tag;
            }
            public function getValue(): mixed
            {
                return $this->values[$this->tag] ?? null;
            }
        });

        return $mock;
    }
}
