<?php

namespace Aurabx\DicomWebParser\Elements;

use Aurabx\DicomWebParser\Elements\ElementParserInterface;

class DateParser implements ElementParserInterface
{
    public static function parse(array $element): mixed
    {
        $value = $element['Value'] ?? null;

        if (is_array($value)) {
            return array_map(static fn($v) => (string) $v, $value);
        }

        return $value !== null ? (string) $value : null;
    }
}
