<?php

namespace Aurabx\DicomWebParser;

use Aurabx\DicomWebParser\DicomModel\DicomElement;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\DicomModel\DicomSequence;
use Aurabx\DicomWebParser\DicomModel\DicomSequenceItem;
use Aurabx\DicomWebParser\DicomModel\DicomSeries;
use Aurabx\DicomWebParser\DicomModel\DicomStudy;
use Aurabx\DicomWebParser\Elements\AttributeTagParser;
use Aurabx\DicomWebParser\Elements\BinaryParser;
use Aurabx\DicomWebParser\Elements\DateParser;
use Aurabx\DicomWebParser\Elements\DateTime;
use Aurabx\DicomWebParser\Elements\DecimalParser;
use Aurabx\DicomWebParser\Elements\FloatingPointParser;
use Aurabx\DicomWebParser\Elements\IntegerParser;
use Aurabx\DicomWebParser\Elements\PersonNameParser;
use Aurabx\DicomWebParser\Elements\TimeParser;

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
    public function parseStudy(string|array $jsonData, string|array|null $order = null): DicomStudy
    {
        $instances = $this->parseInstances($jsonData);

        // Group instances by series UID
        $seriesMap = [];
        foreach ($instances as $instance) {
            $seriesUid = $instance->getElementFirstValue('0020000E');
            if (!isset($seriesMap[$seriesUid])) {
                $seriesMap[$seriesUid] = [];
            }
            $seriesMap[$seriesUid][] = $instance;
        }

        // Create series objects
        $seriesList = [];
        foreach ($seriesMap as $seriesInstances) {
            $seriesList[] = new DicomSeries($seriesInstances);
        }

        // Use the first instance to get study-level information
        $firstInstance = $instances[0] ?? null;
        if (!$firstInstance) {
            throw new ParserException('No instances found to create study');
        }

        $dicomStudy = new DicomStudy($firstInstance->getElementFirstValue('0020000D'), $seriesList);

        if (!empty($order)) {
            if (!is_array($order)) {
                $order = [
                    $order
                ];
            }

            foreach ($order as $ord) {
                $dicomStudy->orderSeries($ord);
            }
        }

        return $dicomStudy;
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
            $instance->addElement($tag, $this->parseElement($element, $tag));
        }

        return $instance;
    }

    /**
     * Parse a DICOM element from the JSON representation with enhanced type handling
     *
     * @param  array  $element  DICOM element data
     * @param  string  $tag
     * @return DicomElement
     */
    protected function parseElement(array $element, string $tag): DicomElement
    {
        $vr = $element['vr'] ?? '';
        $value = null;

        // Handle different VR types
        if (isset($element['Value'])) {
            switch ($vr) {
                case 'SQ': // Sequence
                    $sequence = new DicomSequence($tag);

                    foreach ($element['Value'] as $element_items) {
                        $item = new DicomSequenceItem();

                        if(!empty($element_items)) {
                            foreach ($element_items as $element_key => $element_item) {
                                $item->addElement($element_key, $this->parseElement($element_item, $element_key));
                            }
                        }

                        $sequence->addSequenceItem($item);
                    }

                    $value = $sequence;
                    break;

                case 'PN': // Person Name
                    $value = PersonNameParser::parse($element);
                    break;

                case 'DA': // Date
                    $value = DateParser::parse($element);
                    break;

                case 'TM': // Time
                    $value = TimeParser::parse($element);
                    break;

                case 'DT': // DateTime
                    $value = DateTime::parse($element);
                    break;

                case 'AT': // Attribute Tag
                    $value = AttributeTagParser::parse($element);
                    break;

                case 'DS': // Decimal String
                    $value = DecimalParser::parse($element);
                    break;

                case 'IS': // Integer String
                    $value = IntegerParser::parse($element);
                    break;

                case 'FL': // Floating Point Single
                case 'FD': // Floating Point Double
                    $value = FloatingPointParser::parse($element);
                    break;

                case 'OB': // Other Byte
                case 'OW': // Other Word
                case 'OF': // Other Float
                case 'OD': // Other Double
                    $value = BinaryParser::parse($element);
                    break;

                case 'UI': // Unique Identifier
                    $value = $element['Value'];
                    break;

                case 'UN': // Unknown
                    $value = $element; // Keep as is, may be binary
                    break;

                default:
                    $value = $element['Value'];
                    break;
            }
        }

        return new DicomElement($tag, $vr, $value);
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
