<?php

namespace Aurabx\DicomWebParser\Elements;

class FloatingPointParser implements ElementParserInterface
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
