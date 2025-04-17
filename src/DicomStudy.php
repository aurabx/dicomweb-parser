<?php

namespace Aurabx\DicomWebParser;

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
     * Create a new DICOM study
     *
     * @param string $studyInstanceUid Study instance UID
     * @param array<DicomSeries> $series Series to include
     */
    public function __construct(string $studyInstanceUid, array $series = [])
    {
        $this->series = $series;
        $this->studyInstanceUid = $studyInstanceUid;
    }

    /**
     * Get all series in this study
     *
     * @return array<DicomSeries>
     */
    public function getSeries(): array
    {
        return $this->series;
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
     * Get all instances across all series
     *
     * @return array<DicomInstance>
     */
    public function getAllInstances(): array
    {
        $instances = [];
        foreach ($this->series as $series) {
            $instances = array_merge($instances, $series->getInstances());
        }
        return $instances;
    }

    /**
     * Get study date
     *
     * @return \DateTimeImmutable|null
     */
    public function getStudyDate(): ?\DateTimeImmutable
    {
        if (empty($this->series) || empty($this->series[0]->getInstances())) {
            return null;
        }

        $firstInstance = $this->series[0]->getInstances()[0];
        $dateValue = $firstInstance->getFirstValue('00080020');

        if (!$dateValue) {
            return null;
        }

        if ($dateValue instanceof \DateTimeImmutable) {
            return $dateValue;
        }

        // Parse date if not already parsed
        try {
            // DICOM date format: YYYYMMDD
            if (is_string($dateValue) && strlen($dateValue) === 8) {
                $year = substr($dateValue, 0, 4);
                $month = substr($dateValue, 4, 2);
                $day = substr($dateValue, 6, 2);
                return new \DateTimeImmutable("$year-$month-$day");
            }
        } catch (\Exception $e) {
            // Return null if parsing fails
        }

        return null;
    }

    /**
     * Get study description
     *
     * @return string|null
     */
    public function getStudyDescription(): ?string
    {
        if (empty($this->series) || empty($this->series[0]->getInstances())) {
            return null;
        }

        return $this->series[0]->getInstances()[0]->getFirstValue('00081030');
    }

    /**
     * Get patient name
     *
     * @return string|array|null
     */
    public function getPatientName(): array|string|null
    {
        if (empty($this->series) || empty($this->series[0]->getInstances())) {
            return null;
        }

        return $this->series[0]->getInstances()[0]->getFirstValue('00100010');
    }

    /**
     * Get patient ID
     *
     * @return string|null
     */
    public function getPatientId(): ?string
    {
        if (empty($this->series) || empty($this->series[0]->getInstances())) {
            return null;
        }

        return $this->series[0]->getInstances()[0]->getFirstValue('00100020');
    }

    /**
     * @param  string  $name
     * @return mixed
     */
    public function getFirstValueByName(string $name): mixed
    {
        if (empty($this->series) || empty($this->series[0]->getInstances())) {
            return null;
        }

        return $this->series[0]->getInstances()[0]->getFirstValueByName($name);
    }
}
