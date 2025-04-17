<?php

namespace Aurabx\DicomWebParser\Elements;

class DecimalParser implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $dsValue) {
            // Attempt to convert string to float
            if (is_string($dsValue)) {
                $result[] = (float)$dsValue;
            } else {
                $result[] = $dsValue; // Keep as is if already numeric or other
            }
        }

        return $result;
    }
}
