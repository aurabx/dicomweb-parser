<?php

namespace Aurabx\DicomWebParser\Elements;

class IntegerParser implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $isValue) {
            // Attempt to convert string to integer
            if (is_string($isValue)) {
                $result[] = (int)$isValue;
            } else {
                $result[] = $isValue; // Keep as is if already numeric or other
            }
        }

        return $result;
    }
}
