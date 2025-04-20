<?php

namespace Aurabx\DicomWebParser;

use Aurabx\DicomData\DicomDictionary;
use Aurabx\DicomData\DicomTagLoader;
use Aurabx\DicomData\DicomTag;

/**
 * Responsible for loading and providing access to DICOM tag definitions
 */
class DicomTagService
{
    /**
     * @var array<string, array<string, mixed>> Loaded tag data
     */
    private array $tagData = [];

    /**
     * @var array<string, string> Mapping from tag name to tag ID
     */
    private array $tagByName = [];

    /**
     * @var array<string, string> Value Representation codes and their meanings
     */
    private array $vrMeanings = [
        'AE' => 'Application Entity',
        'AS' => 'Age String',
        'AT' => 'Attribute Tag',
        'CS' => 'Code String',
        'DA' => 'Date',
        'DS' => 'Decimal String',
        'DT' => 'Date Time',
        'FD' => 'Floating Point Double',
        'FL' => 'Floating Point Single',
        'IS' => 'Integer String',
        'LO' => 'Long String',
        'LT' => 'Long Text',
        'OB' => 'Other Byte',
        'OD' => 'Other Double',
        'OF' => 'Other Float',
        'OL' => 'Other Long',
        'OW' => 'Other Word',
        'PN' => 'Person Name',
        'SH' => 'Short String',
        'SL' => 'Signed Long',
        'SQ' => 'Sequence of Items',
        'SS' => 'Signed Short',
        'ST' => 'Short Text',
        'TM' => 'Time',
        'UC' => 'Unlimited Characters',
        'UI' => 'Unique Identifier',
        'UL' => 'Unsigned Long',
        'UN' => 'Unknown',
        'UR' => 'URI/URL',
        'US' => 'Unsigned Short',
        'UT' => 'Unlimited Text'
    ];

    public function __construct(
        public ?DicomTagLoader $dicomTagLoader = null
    )
    {
        if ($dicomTagLoader === null) {
            $this->dicomTagLoader = new DicomTagLoader();
        }
    }

    /**
     * @param  string  $tagId
     * @return array|null
     */
    public function getTagInfo(string $tagId): ?array
    {
        return $this->dicomTagLoader->getTag($tagId);
    }

    /**
     * @param  string  $tagId
     * @return string|null
     */
    public function getTagName(string $tagId): ?string
    {
        $tag = $this->getTagInfo($tagId);

        return $this->dataGet('name', $tag);
    }

    /**
     * @param  string  $name
     * @return string|null
     */
    public function getTagIdByName(string $name): ?string
    {
        return $this->dicomTagLoader->getTagIdByName($name);
    }

    /**
     * @param  string  $tagId
     * @return string|null
     */
    public function getTagVR(string $tagId): ?string
    {
        $tag = $this->getTagInfo($tagId);

        return $this->dataGet('vr', $tag);
    }

    /**
     * @param  string  $tagId
     * @return string|null
     */
    public function getTagDescription(string $tagId): ?string
    {
        $tag = $this->getTagInfo($tagId);

        return $this->dataGet('description', $tag);
    }

    /**
     * @param  string  $vr
     * @return string|null
     */
    public function getVRMeaning(string $vr): ?string
    {
        return $this->dicomTagLoader->getVrMeaning($vr);
    }

    /**
     * @return array
     */
    public function getAllTags(): array
    {
        return $this->dicomTagLoader->getAllTags();
    }

    /**
     * @return array
     */
    public function getAllVRs(): array
    {
        return $this->dicomTagLoader->getAllVRs();
    }

    private function dataGet(string $key, ?array $data, mixed $default = null) {

        if (!empty($data) && array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }
}
