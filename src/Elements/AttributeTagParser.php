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
            } else {
                // For binary AT representation in JSON, might be an array of group/element
                if (is_array($tagValue) && count($tagValue) === 2) {
                    $group = sprintf('%04X', $tagValue[0]);
                    $element = sprintf('%04X', $tagValue[1]);
                    $result[] = $group . $element;
                } else {
                    $result[] = $tagValue; // Keep original if format is unexpected
                }
            }
        }

        return $result;
    }
}
