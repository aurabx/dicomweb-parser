<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit;

use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\DicomModel\DicomStudy;
use PHPUnit\Framework\TestCase;

class DicomStudyReorderingTest extends TestCase
{
    /**
     * Helper to create a dummy DicomInstance.
     *
     * This anonymous class extends DicomInstance and overrides the minimal methods
     * required by DicomSeries and ordering functionality.
     *
     * @param array $data Keys represent DICOM tag IDs (e.g. "00200011" for series number)
     *                    or logical names ("series_number") to simulate our test cases.
     * @return DicomInstance
     */
    private function createDummyInstance(array $data): DicomInstance
    {
        return new class($data) extends DicomInstance {
            private array $data;

            public function __construct(array $data)
            {
                parent::__construct();
                $this->data = $data;
            }

            public function hasElement(string $tag): bool
            {
                // Check if a mapping exists for logical keys.
                $value = $this->getElementFirstValue($tag);
                return $value !== null;
            }

            public function getElementFirstValue(string $tag): mixed
            {
                // Map logical names used by the ordering trait to actual tags.
                return match ($tag) {
                    '00200011' => $this->data['00200011'] ?? null,
                    '00080021' => $this->data['00080021'] ?? null,
                    '00080020' => $this->data['00080020'] ?? null,
                    default    => $this->data[$tag] ?? null,
                };
            }

            public function getElementFirstValueByKeyword(string $keyword): mixed
            {
                // Minimal mapping for keywords.
                $map = [
                    'SeriesInstanceUID' => '0020000E',
                    'Modality'          => '00080060',
                    'SeriesNumber'      => '00200011',
                ];

                if (isset($map[$keyword])) {
                    return $this->getElementFirstValue($map[$keyword]);
                }

                return $this->getElementFirstValue($keyword);
            }
        };
    }

    /**
     * Helper to create a dummy DicomSeries.
     *
     * Creates a valid DicomSeries by injecting a single dummy DicomInstance using the
     * provided $values. Ensure that $values contains a key "0020000E" for the Series UID.
     *
     * @param array $values Dummy instance values keyed by tag.
     * @return DicomSeries
     */
    private function createDummySeries(array $values): DicomSeries
    {
        $instance = $this->createDummyInstance($values);
        return new DicomSeries([$instance]);
    }

    public function testOrderSeriesByNumber(): void
    {
        // Create three dummy series with "series_number" values.
        // A series with an invalid (null) series number should come last.
        $series1 = $this->createDummySeries([
            '00200011' => '2',             // actual series number tag
            '0020000E' => 'uid-2',          // series UID
        ]);
        $series2 = $this->createDummySeries([
            '00200011' => null,            // invalid series number
            '0020000E' => 'uid-null',       // series UID
        ]);
        $series3 = $this->createDummySeries([
            '00200011' => '1',             // actual series number tag
            '0020000E' => 'uid-1',          // series UID
        ]);

        // Create a study with series in unsorted order.
        $study = new DicomStudy('study-uid', [$series1, $series2, $series3]);

        // Reorder series by series number.
        $study->orderSeries(DicomStudy::ORDER_SERIES_NUMBER);
        $orderedSeries = $study->getSeries();

        // Expected order:
        // - series with series_number '1' first,
        // - then series with series_number '2',
        // - followed by series with an invalid series_number.
        $this->assertSame('uid-1', $orderedSeries[0]->getSeriesInstanceUid());
        $this->assertSame('uid-2', $orderedSeries[1]->getSeriesInstanceUid());
        $this->assertSame('uid-null', $orderedSeries[2]->getSeriesInstanceUid());
    }

    public function testOrderSeriesBySeriesDate(): void
    {
        // Create dummy series with "series_date" values.
        $series1 = $this->createDummySeries([
            '00080021' => '20230101',      // actual series date tag
            '0020000E' => 'uid-early',
        ]);
        $series2 = $this->createDummySeries([
            '00080021' => '20230201',      // actual series date tag
            '0020000E' => 'uid-middle',
        ]);
        $series3 = $this->createDummySeries([
            '00080021' => '',              // empty/invalid date should be placed at the end.
            '0020000E' => 'uid-invalid',
        ]);

        // Create a study with series in unsorted order.
        $study = new DicomStudy('study-uid', [$series2, $series3, $series1]);

        // Reorder series by series date.
        $study->orderSeries(DicomStudy::ORDER_SERIES_DATE);
        $orderedSeries = $study->getSeries();

        // Expected order:
        // - series with date '20230101' (earliest),
        // - then series with '20230201',
        // - followed by the invalid series.
        $this->assertSame('uid-early', $orderedSeries[0]->getSeriesInstanceUid());
        $this->assertSame('uid-middle', $orderedSeries[1]->getSeriesInstanceUid());
        $this->assertSame('uid-invalid', $orderedSeries[2]->getSeriesInstanceUid());
    }

    public function testOrderSeriesByStudyDate(): void
    {
        // Create dummy series with "study_date" values.
        $series1 = $this->createDummySeries([
            '00080020' => '20221231',      // actual study date tag
            '0020000E' => 'uid-old',
        ]);
        $series2 = $this->createDummySeries([
            '00080020' => '20230115',      // actual study date tag
            '0020000E' => 'uid-newer',
        ]);
        $series3 = $this->createDummySeries([
            '00080020' => null,            // invalid value (null) should be placed at the end.
            '0020000E' => 'uid-invalid',
        ]);

        // Create a study with series in unsorted order.
        $study = new DicomStudy('study-uid', [$series2, $series3, $series1]);

        // Reorder series by study date.
        $study->orderSeries(DicomStudy::ORDER_EARLIEST_STUDY_DATE);
        $orderedSeries = $study->getSeries();

        // Expected order:
        // - series with study_date '20221231' first,
        // - then series with '20230115',
        // - followed by the invalid series.
        $this->assertSame('uid-old', $orderedSeries[0]->getSeriesInstanceUid());
        $this->assertSame('uid-newer', $orderedSeries[1]->getSeriesInstanceUid());
        $this->assertSame('uid-invalid', $orderedSeries[2]->getSeriesInstanceUid());
    }

    public function testOrderByStudyDateDiffersFromSeriesNumberOrder(): void
    {
        // Create eight series with custom series numbers and study dates
        // so that the inherent series-number order:
        //   [1, 2, 3, 4, 5, 6, 7, 8]
        // is different from the order when reordering by study date.
        //
        // In this scenario, we assign:
        //   - Series G: series number 1, study date "2022-12-31" (earlier)
        //   - Series D: series number 2, no study date
        //   - Series B: series number 3, no study date
        //   - Series H: series number 4, no study date
        //   - Series A: series number 5, study date "2023-01-15" (later)
        //   - Series F: series number 6, no study date
        //   - Series E: series number 7, study date "2023-01-15" (later)
        //   - Series C: series number 8, study date "2022-12-31" (earlier)
        //
        // Insertion order (for the study) is as defined below.
        $seriesA = $this->createDummySeries([
            '00200011' => '5',             // series number 5
            '00080020' => '2023-01-15',      // later
            '0020000E' => 'uid-A',
        ]);
        $seriesB = $this->createDummySeries([
            '00200011' => '3',             // series number 3
            // no study date
            '0020000E' => 'uid-B',
        ]);
        $seriesC = $this->createDummySeries([
            '00200011' => '8',             // series number 8
            '00080020' => '2022-12-31',      // earlier
            '0020000E' => 'uid-C',
        ]);
        $seriesD = $this->createDummySeries([
            '00200011' => '2',             // series number 2
            // no study date
            '0020000E' => 'uid-D',
        ]);
        $seriesE = $this->createDummySeries([
            '00200011' => '7',             // series number 7
            '00080020' => '2023-01-15',      // later
            '0020000E' => 'uid-E',
        ]);
        $seriesF = $this->createDummySeries([
            '00200011' => '6',             // series number 6
            // no study date
            '0020000E' => 'uid-F',
        ]);
        $seriesG = $this->createDummySeries([
            '00200011' => '1',             // series number 1
            '00080020' => '2022-12-31',      // earlier
            '0020000E' => 'uid-G',
        ]);
        $seriesH = $this->createDummySeries([
            '00200011' => '4',             // series number 4
            // no study date
            '0020000E' => 'uid-H',
        ]);

        // Create a study with these series in the insertion order.
        $study = new DicomStudy('study-uid', [
            $seriesA, $seriesB, $seriesC, $seriesD,
            $seriesE, $seriesF, $seriesG, $seriesH,
        ]);

        // --- Reorder by series number ---
        // This ordering will produce the inherent order based on series_number:
        // Expected inherent order: [1, 2, 3, 4, 5, 6, 7, 8] corresponding to:
        // [uid-G, uid-D, uid-B, uid-H, uid-A, uid-F, uid-E, uid-C]
        $study->orderSeries(DicomStudy::ORDER_SERIES_NUMBER);
        $orderedByNumber = $study->getSeries();
        $this->assertSame('uid-G', $orderedByNumber[0]->getSeriesInstanceUid());
        $this->assertSame('uid-D', $orderedByNumber[1]->getSeriesInstanceUid());
        $this->assertSame('uid-B', $orderedByNumber[2]->getSeriesInstanceUid());
        $this->assertSame('uid-H', $orderedByNumber[3]->getSeriesInstanceUid());
        $this->assertSame('uid-A', $orderedByNumber[4]->getSeriesInstanceUid());
        $this->assertSame('uid-F', $orderedByNumber[5]->getSeriesInstanceUid());
        $this->assertSame('uid-E', $orderedByNumber[6]->getSeriesInstanceUid());
        $this->assertSame('uid-C', $orderedByNumber[7]->getSeriesInstanceUid());

        // --- Reorder by earliest study date ---
        // The ordering will bring series with valid study dates first:
        //   Earliest valid ("2022-12-31"): uid-C then uid-G (maintaining original order)
        //   Then later valid ("2023-01-15"): uid-A then uid-E
        //   Finally, series with null study date in their insertion order: uid-B, uid-D, uid-F, uid-H.
        $study->orderSeries(DicomStudy::ORDER_EARLIEST_STUDY_DATE);
        $orderedByStudyDate = $study->getSeries();
        $this->assertSame('uid-G', $orderedByStudyDate[0]->getSeriesInstanceUid());
        $this->assertSame('uid-C', $orderedByStudyDate[1]->getSeriesInstanceUid());
        $this->assertSame('uid-A', $orderedByStudyDate[2]->getSeriesInstanceUid());
        $this->assertSame('uid-E', $orderedByStudyDate[3]->getSeriesInstanceUid());
        $this->assertSame('uid-D', $orderedByStudyDate[4]->getSeriesInstanceUid());
        $this->assertSame('uid-B', $orderedByStudyDate[5]->getSeriesInstanceUid());
        $this->assertSame('uid-H', $orderedByStudyDate[6]->getSeriesInstanceUid());
        $this->assertSame('uid-F', $orderedByStudyDate[7]->getSeriesInstanceUid());

        // --- Assert that the new ordering based on study date is different from the inherent series number order ---
        $this->assertNotEquals(
            array_map(fn($s) => $s->getSeriesInstanceUid(), $orderedByStudyDate),
            array_map(fn($s) => $s->getSeriesInstanceUid(), $orderedByNumber)
        );
    }
}
