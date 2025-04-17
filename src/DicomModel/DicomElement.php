<?php

namespace Aurabx\DicomWebParser\DicomModel;

/**
 * Represents a DICOM element (attribute/value pair)
 */
class DicomElement
{
    private string $vr;
    private $value;

    /**
     * Create a new DICOM element
     *
     * @param string $vr Value Representation (VR) code
     * @param mixed $value Element value(s)
     */
    public function __construct(string $vr, mixed $value = null)
    {
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
        return $this->value;
    }

    /**
     * Get the first value in a multi-valued element
     *
     * @return mixed|null
     */
    public function getFirstValue(): mixed
    {
        if (is_array($this->value) && count($this->value) > 0) {
            return $this->value[0];
        }

        return $this->value;
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
