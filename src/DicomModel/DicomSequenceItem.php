<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagService;

/**
 * There is no data structure to capture individual items in a Sequence in the DICOM model.
 */
class DicomSequenceItem
{
    use HasElements;

    private DicomTagService $dicomTagService;

    public function __construct(
        ?DicomTagService $tagNameResolver = null
    ) {
        $this->dicomTagService = $tagNameResolver ?? new DicomTagService();
    }

}
