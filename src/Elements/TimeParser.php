<?php

namespace Aurabx\DicomWebParser\Elements;

class TimeParser implements ElementParserInterface
{
    /**
     * Parse time values
     *
     * @param  array  $element Array of time values
     * @return array Parsed times
     */
    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $timeStr) {
            try {
                // DICOM time format can be various formats like HHMMSS.FFFFFF
                $hours = substr($timeStr, 0, 2);
                $minutes = substr($timeStr, 2, 2);
                $seconds = substr($timeStr, 4, 2);
                $fractions = substr($timeStr, 6);

                if ($fractions) {
                    $result[] = "$hours:$minutes:$seconds.$fractions";
                } else {
                    $result[] = "$hours:$minutes:$seconds";
                }
            } catch (\Exception $e) {
                $result[] = $timeStr; // Keep original if parsing fails
            }
        }

        return $result;
    }
}
