<?php

namespace Aurabx\DicomWebParser\Elements;

use Aurabx\DicomWebParser\Elements\ElementParserInterface;

class DateParser implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $dateStr) {
            try {
                // DICOM date format: YYYYMMDD
                if (strlen($dateStr) === 8) {
                    $year = substr($dateStr, 0, 4);
                    $month = substr($dateStr, 4, 2);
                    $day = substr($dateStr, 6, 2);
                    $result[] = new \DateTimeImmutable("$year-$month-$day");
                } else {
                    $result[] = $dateStr;
                }
            } catch (\Exception $e) {
                $result[] = $dateStr; // Keep original if parsing fails
            }
        }

        return $result;
    }
}
