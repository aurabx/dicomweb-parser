<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;

/**
 * Represents a DICOM study (collection of series)
 */
class DicomStudy
{
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
        "00100020", // PatientID
        "00101000", // OtherPatientIDs
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

    private DicomTagService $dicomTagService;

    /**
     * Create a new DICOM study
     *
     * @param string $studyInstanceUid Study instance UID
     * @param array<DicomSeries> $series Series to include
     */
    public function __construct(
        string $studyInstanceUid,
        array $series = [],
        ?DicomTagService $tagNameResolver = null
    ) {
        $this->studyInstanceUid = $studyInstanceUid;
        $this->series = $series;
        $this->dicomTagService = $tagNameResolver ?? new DicomTagService();
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
     * Get all series in this study, or a specific series
     *
     * @param  string|null  $index
     * @return DicomSeries|array|null
     */
    public function getSeries(?string $index = null): DicomSeries|array|null
    {
        if ($index !== null) {
            if (!empty($this->series)) {
                if (array_key_exists($index, $this->series)) {
                    return $this->series[$index];
                }
            }

            return null;
        }

        return $this->series;
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function getSeriesFlatArray(): array
    {
        $result = [];
        if (!empty($this->series)) {
            foreach($this->series as $series) {
                $result[] = $series->toArray();
            }
        }

        return $result;
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function getSeriesNamedFlatArray(): array
    {
        $result = [];
        if (!empty($this->series)) {
            foreach($this->series as $series) {
                $result[] = $series->toNamedArray();
            }
        }

        return $result;
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(): array
    {
        $result = [];

        $first = $this->getSeries(0);
        if ($first === null) {
            return [];
        }

        foreach ($this->studyLevelTags as $tag) {
            if ($first->getFirstValue($tag)) {
                $result[$tag] = $first->getFirstValue($tag);
            }
        }

        return $result;
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toNamedArray(): array
    {
        $result = [];

        $first = $this->getSeries(0);
        if ($first === null) {
            return [];
        }

        foreach ($this->studyLevelTags as $tag) {
            if ($first->getFirstValue($tag)) {
                $result[$this->dicomTagService->getTagName($tag)] = $first->getFirstValue($tag);
            }
        }

        return $result;
    }



}
