<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;

/**
 * Represents a DICOM instance (single image or object)
 */
class DicomInstance
{
    /**
     * @var array<string, DicomElement> DICOM elements keyed by tag
     */
    private array $elements = [];

    private DicomTagService $dicomTagService;

    public function __construct(
        ?DicomTagService $tagNameResolver = null
    ) {
        $this->dicomTagService = $tagNameResolver ?? new DicomTagService();
    }

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
     * Get the first value for a tag (handles arrays)
     *
     * @param string $tag
     * @return mixed|null
     */
    public function getFirstValue(string $tag): mixed
    {
        $element = $this->getElement($tag);
        if ($element === null) {
            return null;
        }

        return $element->getValue();
    }

    /**
     * Get the first value of a DICOM element by name or tag
     *
     * @param string $key Attribute name or tag (e.g., 'PatientID' or '00100020')
     * @return mixed|null
     */
    public function getFirstValueByName(string $key): mixed
    {
        $tag = preg_match('/^[0-9A-F]{8}$/i', $key)
            ? $key
            : $this->dicomTagService->getTagIdByName($key);

        return $tag ? $this->getFirstValue($tag) : null;
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
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
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toNamedArray(): array
    {
        $result = [];

        foreach ($this->elements as $tag => $element) {
            $result[$this->dicomTagService->getTagName($tag)] = [
                'vr' => $element->getVR(),
                'value' => $element->getValue()
            ];
        }

        return $result;
    }

    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function toFlatArray(): array
    {
        $result = [];

        foreach ($this->elements as $tag => $element) {
            $result[$tag] = $element->getValue();
        }

        return $result;
    }


    /**
     * Convert instance to flat array (tag → string value)
     *
     * @return array<string, string>
     */
    public function toNamedFlatArray(): array
    {
        $result = [];

        foreach ($this->elements as $tag => $element) {
            $result[$this->dicomTagService->getTagName($tag)] = $element->getValue();
        }

        return $result;
    }

}
