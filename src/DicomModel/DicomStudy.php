<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;
use Aurabx\DicomWebParser\ParserOptions;

/**
 * Represents a DICOM study (collection of series)
 */
class DicomStudy
{
    use OrdersSeries;

    /**
     * @var array<DicomSeries> Series in this study
     */
    private array $series = [];

    /**
     * @var string Study instance UID
     */
    private string $studyInstanceUid;

    /**
     * @var array|string[]
     */
    public array $studyLevelTags = [
        "0020000D", // StudyInstanceUID
        "00080020", // StudyDate
        "00080030", // StudyTime
        "00080090", // ReferringPhysicianName
        "00080050", // AccessionNumber
        "00081030", // StudyDescription
        "00100010", // PatientName
        "00100001", // Other Patient Names
        "00101005", // Patient Birth Name
        "00100020", // PatientID
        "00100022", // Type of identifier
        "00101002", // OtherPatientIDs sequence
        "00100030", // PatientBirthDate
        "00100040", // PatientSex
        "00200010", // StudyID
        "00321032", // RequestingPhysician
        "00321060", // RequestedProcedureDescription
        "00324000", // ImagingServiceRequestComments
        "00081048", // PhysiciansOfRecord
        "00081060", // NameOfPhysiciansReadingStudy
        "00321030", // ReasonForStudy
        "00321070", // RequestedProcedurePriority
        "00080080", // InstitutionName
        "00080081", // InstitutionAddress
        "00080201", // TimezoneOffsetFromUTC
        "00080021", // SeriesDate
        "00080013", // SeriesTime
        "00200011", // SeriesNumber
        "00080022", // AcquisitionDate
        "00080023", // ContentDate
        "00080060", // Modality
        "0008103E", // SeriesDescription
        "0020000E", // SeriesInstanceUID
        "00180015", // BodyPartExamined
    ];

    public const string ORDER_DEFAULT = 'default';
    public const string ORDER_SERIES_NUMBER = 'series-number';
    public const string ORDER_SERIES_DATE = 'series-date';
    public const string ORDER_EARLIEST_STUDY_DATE = 'earliest-date';

    /**
     * The current order.
     *
     * @var string
     */
    public string $order = self::ORDER_DEFAULT;

    /**
     * Create a new DICOM study
     *
     * @param string $studyInstanceUid Study instance UID
     * @param array<DicomSeries> $series Series to include
     */
    public function __construct(
        string $studyInstanceUid,
        array $series = [],
    ) {
        $this->studyInstanceUid = $studyInstanceUid;
        $this->series = $series;
    }

    /**
     * Add a series to this study
     *
     * @param DicomSeries $series
     * @return self
     */
    public function addSeries(DicomSeries $series): self
    {
        $this->series[] = $series;
        return $this;
    }

    /**
     * Get the study instance UID
     *
     * @return string
     */
    public function getStudyInstanceUid(): string
    {
        return $this->studyInstanceUid;
    }

    /**
     * Get the number of series in this study
     *
     * @return int
     */
    public function getSeriesCount(): int
    {
        return count($this->series);
    }

    /**
     * Get the total number of instances across all series
     *
     * @return int
     */
    public function getTotalInstanceCount(): int
    {
        $count = 0;
        foreach ($this->series as $series) {
            $count += $series->getInstanceCount();
        }
        return $count;
    }

    /**
     * @param  string  $tag
     * @return mixed
     */
    public function getFirstValue(string $tag): mixed
    {
        if (empty($this->series)) {
            return null;
        }

        foreach ($this->series as $series) {
            $result = $series->getFirstValue($tag);
            if (!empty($result)) {
                return $result;
            }
        }

        return null;
    }

    /**
     * Get an array of all modalities in this study
     *
     * @return array<string>
     */
    public function getModalities(): array
    {
        $modalities = [];
        foreach ($this->series as $series) {
            $modality = $series->getModality();
            if ($modality && !in_array($modality, $modalities, true)) {
                $modalities[] = $modality;
            }
        }
        return $modalities;
    }

    /**
     * @return bool
     */
    public function hasSeries(): bool
    {
        return !empty($this->series);
    }

    /**
     * Get all series in this study, or a specific series.
     *
     * @param  string|null  $index
     * @param  string|null  $order This won't update the order, just return the series in that order (use orderSeries instead)
     * @return DicomSeries|array|null
     */
    public function getSeries(?string $index = null, ?string $order = null): DicomSeries|array|null
    {
        if ($index !== null) {
            if (!empty($this->series) && array_key_exists($index, $this->series)) {
                return $this->series[$index];
            }

            return null;
        }

        if ($order !== null) {
            if ($order === self::ORDER_EARLIEST_STUDY_DATE && $this->order !== self::ORDER_EARLIEST_STUDY_DATE) {
                return $this->orderSeriesByStudyDate($this->series);
            }

            if ($order === self::ORDER_SERIES_DATE && $this->order !== self::ORDER_SERIES_DATE) {
                return $this->orderSeriesBySeriesDate($this->series);
            }

            if ($order === self::ORDER_SERIES_NUMBER && $this->order !== self::ORDER_SERIES_NUMBER) {
                return $this->orderSeriesByNumber($this->series);
            }
        }

        return $this->series;
    }

    /**
     * Reorder the series.
     *
     * Note that once reordered, the default order cant be restored without reparsing.
     *
     * @param  string  $order
     * @return $this
     */
    public function orderSeries(string $order = self::ORDER_DEFAULT): DicomStudy
    {
        $this->order = $order;

        if ($this->order === self::ORDER_EARLIEST_STUDY_DATE) {
            $this->series = $this->orderSeriesByStudyDate($this->series);
        }

        if ($this->order === self::ORDER_SERIES_DATE) {
            $this->series = $this->orderSeriesBySeriesDate($this->series);
        }

        if ($this->order === self::ORDER_SERIES_NUMBER) {
            $this->series = $this->orderSeriesByNumber($this->series);
        }

        return $this;
    }

    /**
     * @param  string|null  $order
     * @return DicomSeries|null
     */
    public function getFirstSeries(?string $order = null): DicomSeries|null
    {
        if (empty($this->series)) {
            return null;
        }

        $series = $this->series;

        if (!empty($order)) {
            if ($order === self::ORDER_EARLIEST_STUDY_DATE && $this->order !== self::ORDER_EARLIEST_STUDY_DATE) {
                $series = $this->orderSeriesByStudyDate($series);
            }

            if ($order === self::ORDER_SERIES_DATE && $this->order !== self::ORDER_SERIES_DATE) {
                $series = $this->orderSeriesBySeriesDate($series);
            }

            if ($order === self::ORDER_SERIES_NUMBER && $this->order !== self::ORDER_SERIES_NUMBER) {
                $series = $this->orderSeriesByNumber($series);
            }
        }

        return reset($series);
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(string $keys = ParserOptions::USE_TAGS, array $tags = []): array
    {
        if (!$this->hasSeries()) {
            return [];
        }

        return $this->getSeries(0)?->toArray($keys, $tags);
    }
}
