<?php

namespace Aurabx\DicomWebParser\DicomModel;

trait OrdersSeries
{
    /**
     * Orders an array of series by a given key.
     * Entries with empty, null, or invalid values (as determined by the $processor) are placed at the end.
     *
     * @param array $series Array of series data as arrays.
     * @param string $field The key to sort by.
     * @param callable $processor A function to transform the field value into a sortable value. Should return false if the value is invalid.
     * @return array Ordered array of series.
     */
    protected function orderSeriesByField(array $series, string $field, callable $processor): array
    {
        if (empty($series)) {
            return [];
        }

        usort($series, static function ($seriesA, $seriesB) use ($field, $processor) {

            /* @var DicomSeries $seriesA */
            /* @var DicomSeries $seriesB */

            $aValue = $seriesA->getFirstValue($field);
            $bValue = $seriesB->getFirstValue($field);

            // Process the values; if the processor returns false, treat the value as invalid.
            $aProcessed = ($aValue !== null && $aValue !== '') ? $processor($aValue) : false;
            $bProcessed = ($bValue !== null && $bValue !== '') ? $processor($bValue) : false;

            $aValid = $aProcessed !== false;
            $bValid = $bProcessed !== false;

            // If neither is valid, maintain original order
            if (!$aValid && !$bValid) {
                return 0;
            }

            // If only one is valid, it comes first
            if ($aValid && !$bValid) {
                return -1;
            }
            if (!$aValid && $bValid) {
                return 1;
            }

            // Both valid, compare the processed values
            return $aProcessed <=> $bProcessed;
        });

        return $series;
    }

    /**
     * Orders an array of series by the 'series_number' key.
     * Entries with empty, null, or non-numeric 'series_number' values are placed at the end.
     *
     * @param array $series Array of series data as arrays.
     * @return array Ordered array of series.
     */
    protected function orderSeriesByNumber(array $series): array
    {
        return $this->orderSeriesByField($series, '00200011', static function ($value) {
            return is_numeric($value) ? (int)$value : false;
        });
    }

    /**
     * Orders an array of series by the 'series_date' key.
     * Entries with empty, null, or invalid 'series_date' values are placed at the end.
     *
     * @param array $series Array of series data as arrays.
     * @return array Ordered array of series.
     */
    protected function orderSeriesBySeriesDate(array $series): array
    {
        return $this->orderSeriesByField($series, '00080021', static function ($value) {
            return strtotime($value);
        });
    }

    /**
     * Orders an array of series by the 'study_date' key.
     * Entries with empty, null, or invalid 'study_date' values are placed at the end.
     *
     * Note that if all dates are the same there will be no change in the order.
     *
     * @param array $series Array of series data as arrays.
     * @return array Ordered array of series.
     */
    protected function orderSeriesByStudyDate(array $series): array
    {
        return $this->orderSeriesByField($series, '00080020', static function ($value) {
            return strtotime($value);
        });
    }
}
