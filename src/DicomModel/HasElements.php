<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\ParserOptions;

trait HasElements
{
    /**
     * @var array<string, DicomElement> DICOM elements keyed by tag
     */
    private array $elements = [];

    /**
     * Add a DICOM element to this instance
     *
     * @param  string  $tag  DICOM tag (e.g., "00100010")
     * @param  DicomElement  $element  Element to add
     * @return DicomSequenceItem|DicomInstance|HasElements
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
     * A shortcut for DicomInstance::getElement($tag)->getValue();
     *
     * @param string $tag DICOM tag
     * @return mixed|null
     */
    public function getElementValue(string $tag): mixed
    {
        return $this->getElement($tag)?->getValue();
    }

    /**
     * Get the first value for a tag (handles arrays)
     *
     * @param string $tag
     * @return mixed|null
     */
    public function getElementFirstValue(string $tag): mixed
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
     * @param string $keyword Attribute name or tag (e.g., 'PatientID' or '00100020')
     * @return mixed|null
     */
    public function getElementFirstValueByKeyword(string $keyword): mixed
    {
        $tag = preg_match('/^[0-9A-F]{8}$/i', $keyword)
            ? $keyword
            : $this->dicomTagService->getTagIdByKeyword($keyword);

        return $tag ? $this->getElementFirstValue($tag) : null;
    }


    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(string $keys = ParserOptions::USE_TAGS, array $tags = []): array
    {
        $result = [];

        if (!empty($tags)) {
            $elements = array_intersect_key($this->elements, array_flip($tags));
        } else {
            $elements = $this->elements;
        }

        foreach ($elements as $tag => $element) {
            if ($keys === ParserOptions::USE_KEYWORDS) {
                $key = $this->dicomTagService->getTagKeyword($tag);
            } else {
                $key = $tag;
            }

            $result[$key] = $element->toArray($keys);
        }

        return $result;
    }

}