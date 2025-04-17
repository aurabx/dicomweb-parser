<?php

namespace Aurabx\DicomWebParser\Elements;

class DateTime implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        $result = [];

        foreach ($element['Value'] as $dtStr) {
            try {
                // DICOM datetime format: YYYYMMDDHHMMSS.FFFFFF&ZZXX
                // Extract parts
                $date = substr($dtStr, 0, 8); // YYYYMMDD
                $time = substr($dtStr, 8);    // HHMMSS.FFFFFF&ZZXX

                if (strlen($date) === 8) {
                    $year = substr($date, 0, 4);
                    $month = substr($date, 4, 2);
                    $day = substr($date, 6, 2);

                    // Parse time if available
                    $hours = $minutes = $seconds = '00';
                    $fractions = '';
                    $timezone = '';

                    if (!empty($time)) {
                        // Extract time components
                        if (strlen($time) >= 2) {
                            $hours = substr($time, 0, 2);
                        }
                        if (strlen($time) >= 4) {
                            $minutes = substr($time, 2, 2);
                        }
                        if (strlen($time) >= 6) {
                            $seconds = substr($time, 4, 2);
                        }

                        // Handle fractional seconds and timezone
                        $timeParts = explode('.', $time);
                        if (isset($timeParts[1])) {
                            // Split by & to separate fractions and timezone
                            $tzParts = explode('&', $timeParts[1]);
                            $fractions = $tzParts[0];

                            // Handle timezone if present
                            if (isset($tzParts[1])) {
                                $timezone = $tzParts[1];

                                // Convert timezone format to PHP compatible
                                if ($timezone === 'UTC' || $timezone === '+0000') {
                                    $timezone = 'UTC';
                                } else {
                                    // Format like +ZZZZ needs to be converted to +ZZ:ZZ
                                    if (preg_match('/^[+\-]\d{4}$/', $timezone)) {
                                        $timezone = substr($timezone, 0, 3) . ':' . substr($timezone, 3, 2);
                                    }
                                }
                            }
                        }
                    }

                    // Build ISO 8601 format for DateTimeImmutable
                    $isoStr = sprintf(
                        '%s-%s-%sT%s:%s:%s%s%s',
                        $year,
                        $month,
                        $day,
                        $hours,
                        $minutes,
                        $seconds,
                        !empty($fractions) ? '.' . $fractions : '',
                        !empty($timezone) ? $timezone : ''
                    );

                    $result[] = new \DateTimeImmutable($isoStr);
                } else {
                    $result[] = $dtStr; // Keep original if wrong format
                }
            } catch (\Exception $e) {
                $result[] = $dtStr; // Keep original if parsing fails
            }
        }

        return $result;
    }
}
