<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\DicomModel;

use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\DicomModel\DicomStudy;
use Aurabx\DicomWebParser\ParserOptions;
use Aurabx\DicomWebParser\Tests\HasTestData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DicomStudyTest extends TestCase
{
    use HasTestData;

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

}
