<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomData\DicomDictionary;
use Aurabx\DicomWebParser\ParserOptions;

/**
 * Represents a DICOM element (attribute/value pair)
 */
class DicomElement
{
    private string $tag;
    private string $vr;
    private DicomSequence|array|string|null $value;

    /**
     * Create a new DICOM element
     *
     * @param string $vr Value Representation (VR) code
     * @param mixed $value Element value(s)
     */
    public function __construct(string $tag, string $vr, mixed $value = null)
    {
        $this->tag = $tag;
        $this->vr = $vr;
        $this->value = $value;
    }

    /**
     * Get the Value Representation (VR) code
     *
     * @return string
     */
    public function getVR(): string
    {
        return $this->vr;
    }

    /**
     * Get the element value
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        $element_settings = DicomDictionary::getAttributeInfo($this->tag);

        if (!empty($element_settings)) {
            if ($element_settings['valueRepresentation'] === 'SQ' && $this->value instanceof DicomSequence) {
                // We're going to ignore checking the vm here, since some elements specify 1 in the vm but allow many /facepalm
                return $this->value;
            }

            if (array_key_exists('valueMultiplicity', $element_settings) && $element_settings['valueMultiplicity'] !== '1') {
                return $this->value;
            }
        }


        return $this->getFirstValue();
    }

    /**
     * Get the first value in a multi-valued element
     *
     * @return mixed|null
     */
    public function getFirstValue(): mixed
    {
        if (empty($this->value)) {
            return '';
        }

        if (is_array($this->value)) {
            return $this->value[0];
        }

        return $this->value;
    }

    /**
     * Convert instance to array with tag → [vr, value]
     *
     * @return array<string, array{vr: string, value: mixed}>
     */
    public function toArray(string $keys = ParserOptions::USE_TAGS): mixed
    {
        if ($this->vr === 'SQ' && $this->value instanceof DicomSequence) {
            $value = $this->value->toArray($keys);
        } else {
            $value = $this->getValue();
        }

        return $value;
    }

    /**
     * Check if the element has a value
     *
     * @return bool
     */
    public function hasValue(): bool
    {
        return $this->value !== null;
    }

    /**
     * Convert the element to a string representation
     *
     * @return string
     */
    public function __toString(): string
    {
        if (!$this->hasValue()) {
            return '[empty]';
        }

        if (is_array($this->value)) {
            return '[' . implode(', ', array_map('strval', $this->value)) . ']';
        }

        return (string)$this->value;
    }
}
