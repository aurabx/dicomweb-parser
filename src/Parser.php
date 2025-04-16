<?php

namespace Aurabx\DicomWebParser;

use Aurabx\DicomWebParser\DicomElement;
use Aurabx\DicomWebParser\DicomInstance;
use Aurabx\DicomWebParser\DicomSeries;
use Aurabx\DicomWebParser\DicomStudy;
use Aurabx\DicomWebParser\ParserException;

/**
 * Main parser class for converting DICOMWeb JSON responses to PHP objects
 */
class Parser
{
    /**
     * Parse a DICOMWeb JSON response representing a single instance
     *
     * @param string|array $jsonData Raw JSON string or decoded array
     * @return DicomInstance The parsed DICOM instance
     * @throws ParserException
     */
    public function parseInstance(string|array $jsonData): DicomInstance
    {
        $data = $this->prepareJsonData($jsonData);

        if (empty($data)) {
            throw new ParserException('Invalid or empty DICOM JSON data');
        }

        // For a single instance, we expect one array element in the JSON
        if (!isset($data[0])) {
            throw new ParserException('Expected DICOM JSON to contain at least one dataset');
        }

        return $this->createInstanceFromDataset($data[0]);
    }

    /**
     * Parse a DICOMWeb JSON response representing multiple instances
     *
     * @param string|array $jsonData Raw JSON string or decoded array
     * @return array<DicomInstance> Array of parsed DICOM instances
     * @throws ParserException
     */
    public function parseInstances(string|array $jsonData): array
    {
        $data = $this->prepareJsonData($jsonData);

        if (empty($data)) {
            throw new ParserException('Invalid or empty DICOM JSON data');
        }

        $instances = [];
        foreach ($data as $dataset) {
            $instances[] = $this->createInstanceFromDataset($dataset);
        }

        return $instances;
    }

    /**
     * Parse a DICOMWeb JSON response representing a study
     *
     * @param string|array $jsonData Raw JSON string or decoded array
     * @return DicomStudy The parsed DICOM study
     * @throws ParserException
     */
    public function parseStudy(string|array $jsonData): DicomStudy
    {
        $instances = $this->parseInstances($jsonData);

        // Group instances by series UID
        $seriesMap = [];
        foreach ($instances as $instance) {
            $seriesUid = $instance->getSeriesInstanceUid();
            if (!isset($seriesMap[$seriesUid])) {
                $seriesMap[$seriesUid] = [];
            }
            $seriesMap[$seriesUid][] = $instance;
        }

        // Create series objects
        $seriesList = [];
        foreach ($seriesMap as $seriesUid => $seriesInstances) {
            $seriesList[] = new DicomSeries($seriesInstances);
        }

        // Use the first instance to get study-level information
        $firstInstance = $instances[0] ?? null;
        if (!$firstInstance) {
            throw new ParserException('No instances found to create study');
        }

        return new DicomStudy($firstInstance->getStudyInstanceUid(), $seriesList);
    }

    /**
     * Create a DicomInstance object from a dataset
     *
     * @param array $dataset DICOM dataset
     * @return DicomInstance
     */
    protected function createInstanceFromDataset(array $dataset): DicomInstance
    {
        $instance = new DicomInstance();

        foreach ($dataset as $tag => $element) {
            // Parse the tag and add the element to the instance
            $instance->addElement($tag, $this->parseElement($element));
        }

        return $instance;
    }

    /**
     * Parse a DICOM element from the JSON representation
     *
     * @param array $element DICOM element data
     * @return DicomElement
     */
    protected function parseElement(array $element): DicomElement
    {
        $vr = $element['vr'] ?? '';
        $value = null;

        // Handle different VR types
        if (isset($element['Value'])) {
            switch ($vr) {
                case 'SQ': // Sequence
                    $items = [];
                    foreach ($element['Value'] as $item) {
                        $itemElements = [];
                        foreach ($item as $itemTag => $itemElement) {
                            $itemElements[$itemTag] = $this->parseElement($itemElement);
                        }
                        $items[] = $itemElements;
                    }
                    $value = $items;
                    break;

                case 'PN': // Person Name
                    $value = $this->parsePersonName($element['Value']);
                    break;

                case 'DA': // Date
                    $value = $this->parseDates($element['Value']);
                    break;

                case 'TM': // Time
                    $value = $this->parseTimes($element['Value']);
                    break;

                default:
                    $value = $element['Value'];
                    break;
            }
        }

        return new DicomElement($vr, $value);
    }

    /**
     * Parse person name values
     *
     * @param array $values Array of person name values
     * @return array Parsed person names
     */
    protected function parsePersonName(array $values): array
    {
        $result = [];

        foreach ($values as $nameData) {
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

    /**
     * Parse date values
     *
     * @param array $values Array of date values
     * @return array<\DateTimeImmutable> Array of parsed dates
     */
    protected function parseDates(array $values): array
    {
        $result = [];

        foreach ($values as $dateStr) {
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

    /**
     * Parse time values
     *
     * @param array $values Array of time values
     * @return array Parsed times
     */
    protected function parseTimes(array $values): array
    {
        $result = [];

        foreach ($values as $timeStr) {
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

    /**
     * Prepare JSON data for parsing
     *
     * @param string|array $jsonData Raw JSON string or decoded array
     * @return array Decoded JSON data
     * @throws ParserException
     */
    protected function prepareJsonData(string|array $jsonData): array
    {
        if (is_string($jsonData)) {
            try {
                $data = json_decode($jsonData, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new ParserException('Invalid JSON: ' . $e->getMessage());
            }
        } else {
            $data = $jsonData;
        }

        return $data;
    }
}
