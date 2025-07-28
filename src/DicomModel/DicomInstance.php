<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;

/**
 * Represents a DICOM instance (single image or object)
 */
class DicomInstance
{
    use HasElements;

    private DicomTagService $dicomTagService;

    public function __construct(
        ?DicomTagService $tagNameResolver = null
    ) {
        $this->dicomTagService = $tagNameResolver ?? new DicomTagService();
    }
}
