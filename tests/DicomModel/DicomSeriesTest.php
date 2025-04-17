<?php

declare(strict_types=1);

namespace Tests\Unit\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\TagNameResolverInterface;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\ParserException;
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
            tagNameResolver: $this->mockResolver()
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
            tagNameResolver: $this->mockResolver()
        );

        $this->assertSame('9.9.9.9', $series->getSeriesInstanceUid());
    }

    #[Test]
    public function it_throws_if_no_uid_can_be_determined(): void
    {
        $this->expectException(ParserException::class);
        new DicomSeries(instances: [], tagNameResolver: $this->mockResolver());
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
            tagNameResolver: $this->mockResolver()
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
        $instance->method('getFirstValueByName')->willReturn('MatchUID');

        $series = new DicomSeries(
            instances: [$instance],
            tagNameResolver: $this->mockResolver()
        );

        $newInstance = clone $instance;
        $series->addInstance($newInstance);

        $this->assertCount(2, $series->getInstances());
    }

    #[Test]
    public function it_rejects_instance_with_mismatched_uid(): void
    {
        $instance = $this->mockInstance(['0020000E' => 'SeriesA']);
        $instance->method('getFirstValueByName')->willReturn('SeriesA');

        $series = new DicomSeries(
            instances: [$instance],
            tagNameResolver: $this->mockResolver()
        );

        $mismatch = $this->mockInstance(['0020000E' => 'SeriesB']);
        $mismatch->method('getFirstValueByName')->willReturn('SeriesB');

        $this->expectException(ParserException::class);
        $series->addInstance($mismatch);
    }

    #[Test]
    public function it_sorts_instances_by_instance_number(): void
    {
        $inst1 = $this->mockInstance(['00200013' => 20]);
        $inst2 = $this->mockInstance(['00200013' => 5]);
        $inst3 = $this->mockInstance(['00200013' => 10]);

        $series = new DicomSeries([$inst1, $inst2, $inst3], '1.2.3.4.5', tagNameResolver: $this->mockResolver());
        $series->sortInstancesByNumber();

        $sorted = $series->getInstances();
        $this->assertSame(5, $sorted[0]->getFirstValue('00200013'));
        $this->assertSame(10, $sorted[1]->getFirstValue('00200013'));
        $this->assertSame(20, $sorted[2]->getFirstValue('00200013'));
    }

    #[Test]
    public function it_exports_named_array_with_resolver(): void
    {
        // Create a mock DicomElement that returns 'CT'
        $element = $this->createMock(\Aurabx\DicomWebParser\DicomModel\DicomElement::class);
        $element->method('getValue')->willReturn('CT');

        // Create a mock DicomInstance
        $instance = $this->createMock(\Aurabx\DicomWebParser\DicomModel\DicomInstance::class);
        $instance->method('hasElement')->willReturn(true);
        $instance->method('getElement')->willReturn($element);

        // Create a mock TagNameResolver
        $resolver = $this->createMock(TagNameResolverInterface::class);
        $resolver->method('resolve')->willReturn('Modality');

        // Build the DicomSeries with mocks
        $series = new DicomSeries([$instance], '1.2.3.4.5', tagNameResolver: $resolver);
        $result = $series->toNamedArray();

        // Assert expected outcome
        $this->assertSame(['Modality' => 'CT'], $result);
    }


    private function mockResolver(): TagNameResolverInterface
    {
        return new class implements TagNameResolverInterface {
            public function resolve(string $tag): string
            {
                return "Resolved($tag)";
            }

            public function getTagIdByName(string $name): ?string
            {
                // TODO: Implement getTagIdByName() method.
            }
        };
    }

    private function mockInstance(array $values): \PHPUnit\Framework\MockObject\MockObject
    {
        $mock = $this->createMock(DicomInstance::class);
        $mock->method('hasElement')->willReturnCallback(fn($tag) => array_key_exists($tag, $values));
        $mock->method('getFirstValue')->willReturnCallback(fn($tag) => $values[$tag] ?? null);
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
