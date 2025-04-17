<?php

namespace Aurabx\DicomWebParser\Elements;

class PersonNameParser implements ElementParserInterface
{
    public static function parse(array $element): mixed
    {
        $value = $element['Value'] ?? null;

        if (!is_array($value)) {
            return null;
        }

        return array_map(static function ($v) {
            if (is_string($v)) {
                // Parse as DICOM name: Family^Given^Middle^Prefix^Suffix
                $parts = explode('^', $v);

                return [
                    'Alphabetic' => [
                        'FamilyName' => $parts[0] ?? null,
                        'GivenName' => $parts[1] ?? null,
                        'MiddleName' => $parts[2] ?? null,
                        'NamePrefix' => $parts[3] ?? null,
                        'NameSuffix' => $parts[4] ?? null,
                    ],
                ];
            }

            return $v;
        }, $value);
    }
}
