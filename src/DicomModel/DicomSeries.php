<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomDictionary;
use Aurabx\DicomWebParser\DicomDictionaryTagNameResolver;
use Aurabx\DicomWebParser\ParserException;
use Aurabx\DicomWebParser\TagNameResolverInterface;

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
        '0020,000E', // SeriesInstanceUID
        '0020,0011', // SeriesNumber
        '0008,103E', // SeriesDescription
        '0008,0060', // Modality
        '0008,1070', // OperatorsName
        '0008,1090', // ManufacturerModelName
        '0008,0070', // Manufacturer
        '0018,0015', // BodyPartExamined
        '0018,1030', // ProtocolName
        '0018,0024', // SequenceName
        '0018,0021', // EchoTrainLength
        '0008,0021', // SeriesDate
        '0008,0031', // SeriesTime
        '0018,1060', // TriggerTime
        '0018,0081', // EchoTime
        '0008,1111', // ReferencedStudySequence
        '0020,0020', // PatientOrientation
        '0018,5100', // PatientPosition
    ];

    private TagNameResolverInterface $tagNameResolver;

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
        ?TagNameResolverInterface $tagNameResolver = null
    )
    {
        $this->tagNameResolver = $tagNameResolver ?? new DicomDictionaryTagNameResolver();
        $this->instances = $instances;

        if ($seriesInstanceUid) {
            $this->seriesInstanceUid = $seriesInstanceUid;
        } else {
            if (!empty($instances)) {
                $uid = $this->getFirstValue('0020000E');
                if (!$uid) {
                    throw new ParserException('Series instance UID not found in instance');
                }
                $this->seriesInstanceUid = $uid;
            } else {
                throw new ParserException('Cannot create series without instances or explicit UID');
            }
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
        $instanceSeriesUid = $instance->getFirstValueByName('SeriesInstanceUID');

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
                return $instance->getFirstValue($tag);
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
            $aNum = $a->getFirstValue('00200013');
            $bNum = $b->getFirstValue('00200013');

            $aNum = $aNum !== null ? (int)$aNum : 0;
            $bNum = $bNum !== null ? (int)$bNum : 0;

            return $aNum <=> $bNum;
        });

        return $this;
    }

    /**
     * @return array<DicomInstance>
     */
    public function getSeriesInstances(): array
    {
        $instances = [];
        foreach ($this->instances as $instance) {
            $instances[] = $instance->getElements();
        }
        return $instances;
    }

    /**
     * @param  string  $index
     * @return DicomInstance|null
     */
    public function getSeriesInstance(string $index): ?DicomInstance
    {
        if (!empty($this->instances)) {
            if (array_key_exists($index, $this->instances)) {
                return $this->instances[$index];
            }
        }

        return null;
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function getSeriesInstancesFlatArray(): array
    {
        return array_map(static function ($instance) {
            return $instance->toFlatArray();
        }, $this->instances);
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function getSeriesInstancesNamedFlatArray(): array
    {
        return array_map(static function ($instance) {
            return $instance->toNamedFlatArray();
        }, $this->instances);
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(): array
    {
        $result = [];

        $first = $this->getSeriesInstance(0);
        if ($first === null) {
            return [];
        }

        foreach ($this->seriesLevelTags as $tag) {
            if ($first->hasElement($tag)) {
                $result[$tag] = $first->getElement($tag)?->getValue();
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

        $first = $this->getSeriesInstance(0);
        if ($first === null) {
            return [];
        }

        foreach ($this->seriesLevelTags as $tag) {
            if ($first->hasElement($tag)) {
                $result[$this->tagNameResolver->resolve($tag)] = $first->getElement($tag)?->getValue();
            }
        }

        return $result;
    }


}
