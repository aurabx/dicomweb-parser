<?php

namespace Aurabx\DicomWebParser;

use Aurabx\DicomWebParser\ParserException;

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

    /**
     * Create a new DICOM series
     *
     * @param array<DicomInstance> $instances Instances to include
     * @param string|null $seriesInstanceUid Series instance UID (optional, will use from first instance if not provided)
     * @throws ParserException
     */
    public function __construct(array $instances = [], ?string $seriesInstanceUid = null)
    {
        $this->instances = $instances;

        if ($seriesInstanceUid) {
            $this->seriesInstanceUid = $seriesInstanceUid;
        } else if (!empty($instances)) {
            $uid = $instances[0]->getSeriesInstanceUid();
            if (!$uid) {
                throw new ParserException('Series instance UID not found in instance');
            }
            $this->seriesInstanceUid = $uid;
        } else {
            throw new ParserException('Cannot create series without instances or explicit UID');
        }
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
     * Add an instance to this series
     *
     * @param DicomInstance $instance
     * @return self
     * @throws ParserException
     */
    public function addInstance(DicomInstance $instance): self
    {
        $instanceSeriesUid = $instance->getSeriesInstanceUid();

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
        if (empty($this->instances)) {
            return null;
        }

        return $this->instances[0]->getModality();
    }

    /**
     * Get the series number
     *
     * @return int|null
     */
    public function getSeriesNumber(): ?int
    {
        if (empty($this->instances)) {
            return null;
        }

        $value = $this->instances[0]->getFirstValue('00200011');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get series description
     *
     * @return string|null
     */
    public function getSeriesDescription(): ?string
    {
        if (empty($this->instances)) {
            return null;
        }

        return $this->instances[0]->getFirstValue('0008103E');
    }

    /**
     * Sort instances by instance number
     *
     * @return self
     */
    public function sortInstancesByNumber(): self
    {
        usort($this->instances, static function(DicomInstance $a, DicomInstance $b) {
            $aNum = $a->getFirstValue('00200013');
            $bNum = $b->getFirstValue('00200013');

            $aNum = $aNum !== null ? (int)$aNum : 0;
            $bNum = $bNum !== null ? (int)$bNum : 0;

            return $aNum <=> $bNum;
        });

        return $this;
    }



}
