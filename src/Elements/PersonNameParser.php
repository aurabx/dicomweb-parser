<?php

namespace Aurabx\DicomWebParser\Elements;

class PersonNameParser implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $nameData) {
            if (is_array($nameData)) {
                // Person name components
                $name = [
                    'family' => $nameData['Alphabetic']['FamilyName'] ?? null,
                    'given' => $nameData['Alphabetic']['GivenName'] ?? null,
                    'middle' => $nameData['Alphabetic']['MiddleName'] ?? null,
                    'prefix' => $nameData['Alphabetic']['NamePrefix'] ?? null,
                    'suffix' => $nameData['Alphabetic']['NameSuffix'] ?? null,
                ];
                $result[] = $name;
            } else {
                // Simple string name
                $result[] = $nameData;
            }
        }

        return $result;
    }
}
