<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomDictionary;
use Aurabx\DicomWebParser\DicomDictionaryTagNameResolver;
use Aurabx\DicomWebParser\TagNameResolverInterface;

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
        '0020,000D', // StudyInstanceUID
        '0008,0020', // StudyDate
        '0008,0030', // StudyTime
        '0008,0090', // ReferringPhysicianName
        '0008,0050', // AccessionNumber
        '0008,1030', // StudyDescription
        '0010,0010', // PatientName
        '0010,0020', // PatientID
        '0010,0030', // PatientBirthDate
        '0010,0040', // PatientSex
        '0020,0010', // StudyID
        '0032,1032', // RequestingPhysician
        '0032,1060', // RequestedProcedureDescription
        '0032,4000', // StudyComments
        '0008,1048', // PhysiciansOfRecord
        '0008,1060', // NameOfPhysiciansReadingStudy
        '0032,1030', // ReasonForStudy
        '0032,1070', // RequestedProcedurePriority
    ];

    private TagNameResolverInterface $tagNameResolver;

    /**
     * Create a new DICOM study
     *
     * @param string $studyInstanceUid Study instance UID
     * @param array<DicomSeries> $series Series to include
     */
    public function __construct(
        string $studyInstanceUid,
        array $series = [],
        ?TagNameResolverInterface $tagNameResolver = null
    ) {
        $this->studyInstanceUid = $studyInstanceUid;
        $this->series = $series;
        $this->tagNameResolver = $tagNameResolver ?? new DicomDictionaryTagNameResolver();
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
        return array_map(static function ($series) {
            return $series->toArray();
        }, $this->series);
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function getSeriesNamedFlatArray(): array
    {
        return array_map(static function ($series) {
            return $series->toNamedArray();
        }, $this->series);
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
                $result[$tag] = $first->getFirstValue($tag)?->getValue();
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
                $result[$this->tagNameResolver->resolve($tag)] = $first->getFirstValue($tag)?->getValue();
            }
        }

        return $result;
    }



}
