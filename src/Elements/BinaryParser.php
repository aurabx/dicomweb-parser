<?php

namespace Aurabx\DicomWebParser\Elements;

class BinaryParser implements ElementParserInterface
{

    public static function parse(array $element): array
    {
        // If InlineBinary is provided, decode it
        if ($element['InlineBinary'] !== null) {
            return [
                base64_decode($element['InlineBinary'])
            ];
        }

        // If values are provided, return them
        if ($element['Value'] !== null) {
            return $element['Value'];
        }

        return [];
    }
}
