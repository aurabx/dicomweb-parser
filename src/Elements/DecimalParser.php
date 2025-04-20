<?php

namespace Aurabx\DicomWebParser\Elements;

class DecimalParser implements ElementParserInterface
{
    public static function parse(array $element): array
    {
        $value = $element['Value'] ?? null;

        if (is_array($value)) {
            return array_map(static fn($v) => (string) $v, $value);
        }

        return $value !== null ? [(string) $value] : [null];
    }
}
