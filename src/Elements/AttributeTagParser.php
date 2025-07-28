<?php

namespace Aurabx\DicomWebParser\Elements;

use Aurabx\DicomData\DicomDictionary;
use Aurabx\DicomData\DicomTag;

class AttributeTagParser implements ElementParserInterface
{

    public static function parse(array $element): array
    {
        $result = [];

        foreach ($element['Value'] as $tagValue) {
            // If it's a string, normalize it
            if (is_string($tagValue)) {
                $result[] = $tagValue;
            } elseif (is_array($tagValue) && count($tagValue) === 2) {
                $result[] = sprintf('%04X', $tagValue[0]) . sprintf('%04X', $tagValue[1]);
            } else {
                $result[] = $tagValue; // Keep original if format is unexpected
            }
        }

        return $result;
    }
}
