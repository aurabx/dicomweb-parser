<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;
use Aurabx\DicomWebParser\ParserException;
use Aurabx\DicomWebParser\ParserOptions;

/**
 * Represents a DICOM series (collection of instances)
 */
class DicomSeries
{
    /**
     * @var array<DicomInstance> Instances in this series
     */
    private array $instances = [];

    /**
     * @var string Series instance UID
     */
    private string $seriesInstanceUid;

    public array $seriesLevelTags = [
        '0020000E', // SeriesInstanceUID
        '00200011', // SeriesNumber
        '0008103E', // SeriesDescription
        '00080060', // Modality
        '00081070', // OperatorsName
        '00081090', // ManufacturerModelName
        '00080070', // Manufacturer
        '00180015', // BodyPartExamined
        '00181030', // ProtocolName
        '00180024', // SequenceName
        '00180021', // EchoTrainLength
        '00080021', // SeriesDate
        '00080031', // SeriesTime
        '00181060', // TriggerTime
        '00180081', // EchoTime
        '00081111', // ReferencedStudySequence
        '00200020', // PatientOrientation
        '00185100', // PatientPosition
    ];

    /**
     * Create a new DICOM series
     *
     * @param array<DicomInstance> $instances Instances to include
     * @param string|null $seriesInstanceUid Series instance UID (optional, will use from first instance if not provided)
     * @throws ParserException
     */
    public function __construct(
        array $instances = [],
        ?string $seriesInstanceUid = null,
    )
    {
        $this->instances = $instances;

        if ($seriesInstanceUid) {
            $this->seriesInstanceUid = $seriesInstanceUid;
        } elseif (!empty($instances)) {
            $uid = $this->getFirstValue('0020000E');
            if (!$uid) {
                throw new ParserException('Series instance UID not found in instance');
            }
            $this->seriesInstanceUid = $uid;
        } else {
            throw new ParserException('Cannot create series without instances or explicit UID');
        }
    }

    /**
     * Add an instance to this series
     *
     * @param DicomInstance $instance
     * @return self
     * @throws ParserException
     */
    public function addInstance(DicomInstance $instance): self
    {
        $instanceSeriesUid = $instance->getElementFirstValueByKeyword('SeriesInstanceUID');

        if ($instanceSeriesUid !== $this->seriesInstanceUid) {
            throw new ParserException(
                "Instance series UID ($instanceSeriesUid) does not match series UID ({$this->seriesInstanceUid})"
            );
        }

        $this->instances[] = $instance;
        return $this;
    }

    /**
     * Get the series instance UID
     *
     * @return string
     */
    public function getSeriesInstanceUid(): string
    {
        return $this->seriesInstanceUid;
    }

    /**
     * Get the number of instances in this series
     *
     * @return int
     */
    public function getInstanceCount(): int
    {
        return count($this->instances);
    }

    /**
     * Get the modality of this series (from first instance)
     *
     * @return string|null
     */
    public function getModality(): ?string
    {
        return $this->getFirstValue('00080060');
    }

    /**
     * Get the series number
     *
     * @return int|null
     */
    public function getSeriesNumber(): ?int
    {
        return $this->getFirstValue('00200011');
    }

    /**
     * Get series description
     *
     * @return string|null
     */
    public function getSeriesDescription(): ?string
    {
        return $this->getFirstValue('0008103E');
    }

    /**
     * @param  string  $tag
     * @return mixed
     */
    public function getFirstValue(string $tag): mixed
    {
        if (empty($this->instances)) {
            return null;
        }

        foreach ($this->instances as $instance) {
            if ($instance->hasElement($tag)) {
                return $instance->getElementFirstValue($tag);
            }
        }

        return null;
    }

    /**
     * Sort instances by instance number
     *
     * @return self
     */
    public function sortInstancesByNumber(): self
    {
        usort($this->instances, static function(DicomInstance $a, DicomInstance $b) {
            $aNum = $a->getElementFirstValue('00200013');
            $bNum = $b->getElementFirstValue('00200013');

            $aNum = $aNum !== null ? (int)$aNum : 0;
            $bNum = $bNum !== null ? (int)$bNum : 0;

            return $aNum <=> $bNum;
        });

        return $this;
    }

    /**
     * @return bool
     */
    public function hasInstances(): bool
    {
        return !empty($this->instances);
    }

    /**
     * Get all instances in this series
     *
     * @return array<DicomInstance>
     */
    public function getInstances(): array
    {
        return $this->instances;
    }

    /**
     * @param  string  $index
     * @return DicomInstance|null
     */
    public function getInstance(string $index): ?DicomInstance
    {
        if (!empty($this->instances) && array_key_exists($index, $this->instances)) {
            return $this->instances[$index];
        }

        return null;
    }

    /**
     * @return DicomInstance|null
     */
    public function getFirstInstance(): ?DicomInstance
    {
        if (!empty($this->instances)) {
            return reset($this->instances);
        }

        return null;
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(string $keys = ParserOptions::USE_TAGS, array $tags = []): array
    {
        if (!$this->hasInstances()) {
            return [];
        }

        $first = $this->getInstance(0);
        if ($first === null) {
            return [];
        }

        return $first->toArray($keys, $tags);
    }
}
