<?php

namespace Aurabx\DicomWebParser;

/**
 * Represents a DICOM instance (single image or object)
 */
class DicomInstance
{
    /**
     * @var array<string, DicomElement> DICOM elements keyed by tag
     */
    private array $elements = [];

    /**
     * Known tag mappings for quick access to common attributes
     */
    private const TAG_MAPPINGS = [
        'studyInstanceUid' => '0020000D',
        'seriesInstanceUid' => '0020000E',
        'sopInstanceUid' => '00080018',
        'sopClassUid' => '00080016',
        'modality' => '00080060',
        'patientName' => '00100010',
        'patientId' => '00100020',
        'patientBirthDate' => '00100030',
        'studyDate' => '00080020',
        'studyTime' => '00080030',
        'studyDescription' => '00081030',
        'seriesNumber' => '00200011',
        'instanceNumber' => '00200013',
    ];

    /**
     * Add a DICOM element to this instance
     *
     * @param string $tag DICOM tag (e.g., "00100010")
     * @param DicomElement $element Element to add
     * @return self
     */
    public function addElement(string $tag, DicomElement $element): self
    {
        $this->elements[$tag] = $element;
        return $this;
    }

    /**
     * Get a DICOM element by tag
     *
     * @param string $tag DICOM tag
     * @return DicomElement|null
     */
    public function getElement(string $tag): ?DicomElement
    {
        return $this->elements[$tag] ?? null;
    }

    /**
     * Get all elements
     *
     * @return array<string, DicomElement>
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    /**
     * Check if the instance has a specific element
     *
     * @param string $tag DICOM tag
     * @return bool
     */
    public function hasElement(string $tag): bool
    {
        return isset($this->elements[$tag]);
    }

    /**
     * Get the value of an element by tag
     *
     * @param string $tag DICOM tag
     * @return mixed|null
     */
    public function getValue(string $tag): mixed
    {
        return $this->getElement($tag)?->getValue();
    }

    /**
     * Magic method to access common attributes via getters
     *
     * @param string $method Method name
     * @param array $arguments Method arguments
     * @return mixed
     * @throws \BadMethodCallException
     */
    public function __call(string $method, array $arguments)
    {
        // Handle getter methods (e.g., getStudyInstanceUid())
        if (str_starts_with($method, 'get')) {
            $property = lcfirst(substr($method, 3));

            if (isset(self::TAG_MAPPINGS[$property])) {
                return $this->getFirstValue(self::TAG_MAPPINGS[$property]);
            }
        }

        throw new \BadMethodCallException("Method {$method} does not exist");
    }

    /**
     * Get the study instance UID
     *
     * @return string|null
     */
    public function getStudyInstanceUid(): ?string
    {
        return $this->getFirstValue(self::TAG_MAPPINGS['studyInstanceUid']);
    }

    /**
     * Get the series instance UID
     *
     * @return string|null
     */
    public function getSeriesInstanceUid(): ?string
    {
        return $this->getFirstValue(self::TAG_MAPPINGS['seriesInstanceUid']);
    }

    /**
     * Get the SOP instance UID
     *
     * @return string|null
     */
    public function getSopInstanceUid(): ?string
    {
        return $this->getFirstValue(self::TAG_MAPPINGS['sopInstanceUid']);
    }

    /**
     * Get the SOP class UID
     *
     * @return string|null
     */
    public function getSopClassUid(): ?string
    {
        return $this->getFirstValue(self::TAG_MAPPINGS['sopClassUid']);
    }

    /**
     * Get the modality
     *
     * @return string|null
     */
    public function getModality(): ?string
    {
        return $this->getFirstValue(self::TAG_MAPPINGS['modality']);
    }

    /**
     * Convert the instance to an array
     *
     * @return array
     */
    public function toArray(): array
    {
        $result = [];

        foreach ($this->elements as $tag => $element) {
            $result[$tag] = [
                'vr' => $element->getVR(),
                'value' => $element->getValue()
            ];
        }

        return $result;
    }


    /**
     * Get the first value for a tag (e.g. '00100020').
     *
     * @param  string  $tag
     * @return mixed
     */
    public function getFirstValue(string $tag): mixed
    {
        $element = $this->getElement($tag);
        if ($element === null) {
            return null;
        }

        $value = $element->getValue();

        if (is_array($value)) {
            return $value[0] ?? null;
        }

        return $value;
    }

    /**
     * Get the first value of a DICOM element by its attribute name (e.g. 'PatientID')
     *
     * @param  string  $key
     * @return mixed
     */
    public function getFirstValueByName(string $key): mixed
    {
        $tag = preg_match('/^[0-9A-F]{8}$/i', $key)
            ? $key
            : DicomDictionary::getTagIdByName($key);

        return $tag ? $this->getFirstValue($tag) : null;
    }
}
