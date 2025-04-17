<?php

declare(strict_types=1);

namespace Tests\Unit\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomModel\DicomStudy;
use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\DicomDictionary;
use Aurabx\DicomWebParser\TagNameResolverInterface;
use PHPUnit\Framework\TestCase;

final class DicomStudyTest extends TestCase
{
    #[Test]
    public function it_stores_and_returns_study_instance_uid(): void
    {
        $study = new DicomStudy(studyInstanceUid: '1.2.3.4');

        $this->assertSame('1.2.3.4', $study->getStudyInstanceUid());
    }

    #[Test]
    public function it_counts_series_correctly(): void
    {
        $series1 = $this->createMock(DicomSeries::class);
        $series2 = $this->createMock(DicomSeries::class);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series1]);
        $study->addSeries($series2);

        $this->assertSame(2, $study->getSeriesCount());
    }

    #[Test]
    public function it_counts_total_instance_count(): void
    {
        $series1 = $this->createMock(DicomSeries::class);
        $series1->method('getInstanceCount')->willReturn(3);

        $series2 = $this->createMock(DicomSeries::class);
        $series2->method('getInstanceCount')->willReturn(7);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series1, $series2]);

        $this->assertSame(10, $study->getTotalInstanceCount());
    }

    #[Test]
    public function it_returns_first_value_from_series(): void
    {
        $valueObject = new class {
            public function getValue(): string
            {
                return 'TestValue';
            }
        };

        $series1 = $this->createMock(DicomSeries::class);
        $series1->method('getFirstValue')->willReturn(null);

        $series2 = $this->createMock(DicomSeries::class);
        $series2->method('getFirstValue')->willReturn($valueObject);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series1, $series2]);

        $this->assertSame($valueObject, $study->getFirstValue('0008,0050'));
    }

    #[Test]
    public function it_returns_modalities(): void
    {
        $series1 = $this->createMock(DicomSeries::class);
        $series1->method('getModality')->willReturn('CT');

        $series2 = $this->createMock(DicomSeries::class);
        $series2->method('getModality')->willReturn('MR');

        $series3 = $this->createMock(DicomSeries::class);
        $series3->method('getModality')->willReturn('CT');

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series1, $series2, $series3]);

        $this->assertSame(['CT', 'MR'], $study->getModalities());
    }

    #[Test]
    public function it_returns_series_by_index(): void
    {
        $series = $this->createMock(DicomSeries::class);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series]);

        $this->assertSame($series, $study->getSeries('0'));
    }

    #[Test]
    public function it_returns_series_flat_array(): void
    {
        $series = $this->createMock(DicomSeries::class);
        $series->method('toArray')->willReturn(['0008,0050' => 'ACC123']);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series]);

        $this->assertSame([['0008,0050' => 'ACC123']], $study->getSeriesFlatArray());
    }

    #[Test]
    public function it_returns_named_flat_array(): void
    {
        $series = $this->createMock(DicomSeries::class);
        $series->method('toNamedArray')->willReturn(['AccessionNumber' => 'ACC123']);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series]);

        $this->assertSame([['AccessionNumber' => 'ACC123']], $study->getSeriesNamedFlatArray());
    }

    #[Test]
    public function it_returns_array_of_values_for_study_level_tags(): void
    {
        $mockValue = new class {
            public function getValue(): string
            {
                return 'TestValue';
            }
        };

        $series = $this->createMock(DicomSeries::class);
        $series->method('getFirstValue')->willReturn($mockValue);

        $study = new DicomStudy(studyInstanceUid: '1.2.3.4', series: [$series]);

        $array = $study->toArray();

        $this->assertArrayHasKey('0020,000D', $array);
        $this->assertSame('TestValue', $array['0020,000D']);
    }

    #[Test]
    public function it_returns_named_array_of_values_using_tag_name_resolver(): void
    {
        // Create a fake ValueObject
        $mockValueObject = new class {
            public function getValue(): string
            {
                return 'HelloWorld';
            }
        };

        // Create a mock DicomSeries that returns our fake ValueObject
        $series = $this->createMock(DicomSeries::class);
        $series->method('getFirstValue')->willReturn($mockValueObject);

        // Create a mock tag name resolver
        $resolver = $this->createMock(TagNameResolverInterface::class);
        $resolver->method('resolve')->willReturnCallback(
            fn(string $tag) => "Resolved($tag)"
        );

        // Instantiate DicomStudy with resolver
        $study = new DicomStudy(
            studyInstanceUid: '1.2.3.4',
            series: [$series],
            tagNameResolver: $resolver
        );

        $result = $study->toNamedArray();

        // Check if at least one tag was resolved
        $this->assertNotEmpty($result);

        foreach ($result as $key => $value) {
            $this->assertStringStartsWith('Resolved(', $key);
            $this->assertSame('HelloWorld', $value);
        }
    }

}
