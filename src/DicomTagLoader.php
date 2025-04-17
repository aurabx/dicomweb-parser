<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser;

/**
 * Responsible for loading and providing access to DICOM tag definitions
 */
class DicomTagLoader
{
    /**
     * @var array<string, array<string, mixed>> Loaded tag data
     */
    private array $tagData = [];

    /**
     * @var array<string, string> Mapping from tag name to tag ID
     */
    private array $tagByName = [];

    /**
     * @var array<string, string> Value Representation codes and their meanings
     */
    private array $vrMeanings = [
        'AE' => 'Application Entity',
        'AS' => 'Age String',
        'AT' => 'Attribute Tag',
        'CS' => 'Code String',
        'DA' => 'Date',
        'DS' => 'Decimal String',
        'DT' => 'Date Time',
        'FD' => 'Floating Point Double',
        'FL' => 'Floating Point Single',
        'IS' => 'Integer String',
        'LO' => 'Long String',
        'LT' => 'Long Text',
        'OB' => 'Other Byte',
        'OD' => 'Other Double',
        'OF' => 'Other Float',
        'OL' => 'Other Long',
        'OW' => 'Other Word',
        'PN' => 'Person Name',
        'SH' => 'Short String',
        'SL' => 'Signed Long',
        'SQ' => 'Sequence of Items',
        'SS' => 'Signed Short',
        'ST' => 'Short Text',
        'TM' => 'Time',
        'UC' => 'Unlimited Characters',
        'UI' => 'Unique Identifier',
        'UL' => 'Unsigned Long',
        'UN' => 'Unknown',
        'UR' => 'URI/URL',
        'US' => 'Unsigned Short',
        'UT' => 'Unlimited Text'
    ];

    /**
     * Create a new DICOM tag loader
     *
     * @param string|null $tagsPath Path to a specific JSON file, or null to use default paths
     * @throws ParserException If no valid tag definitions can be loaded
     */
    public function __construct(?string $tagsPath = null)
    {
        if ($tagsPath !== null) {
            $this->loadFromFile($tagsPath);
            return;
        }

        // If no path specified, try default locations
        $this->loadDefaultTags();
    }

    /**
     * Try to load tags from default locations
     *
     * @return void
     * @throws ParserException If no tags can be loaded
     */
    private function loadDefaultTags(): void
    {
        // Try to load from resources/tags directory first
        $resourcesDir = dirname(__DIR__) . '/resources/tags';
        if (is_dir($resourcesDir)) {
            $jsonFiles = glob("$resourcesDir/*.json");
            if (!empty($jsonFiles)) {
                foreach ($jsonFiles as $jsonFile) {
                    $this->loadFromFile($jsonFile, false);
                }
                return;
            }
        }

        // If we still have no tags, throw an exception
        throw new ParserException("Could not find any DICOM tag definitions in default locations");
    }

    /**
     * Load tag definitions from a JSON file
     *
     * @param string $jsonPath Path to the JSON file
     * @param bool $clearExisting Whether to clear existing tags (default: true)
     * @return void
     * @throws ParserException If the file cannot be loaded or parsed
     */
    public function loadFromFile(string $jsonPath, bool $clearExisting = true): void
    {
        if (!file_exists($jsonPath)) {
            throw new ParserException("Tag definition file not found: $jsonPath");
        }

        if (is_dir($jsonPath)) {
            throw new ParserException("Expected a file but got a directory: $jsonPath");
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            throw new ParserException("Failed to read tag definition file: $jsonPath");
        }

        try {
            $data = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ParserException("Invalid JSON in tag definition file: " . $e->getMessage());
        }

        $this->loadFromArray($data, $clearExisting);
    }

    /**
     * Load tag definitions from an array
     *
     * @param array<string, array<string, mixed>> $data Tag data
     * @param bool $clearExisting Whether to clear existing tags (default: true)
     * @return void
     */
    public function loadFromArray(array $data, bool $clearExisting = true): void
    {
        if ($clearExisting) {
            $this->tagData = [];
            $this->tagByName = [];
        }

        foreach ($data as $tagId => $tagInfo) {
            // Store tag info by tag ID
            $this->tagData[$tagId] = $tagInfo;

            // Create reverse mapping from name to ID
            if (isset($tagInfo['name'])) {
                $this->tagByName[$tagInfo['name']] = $tagId;
            }
        }
    }

    /**
     * Get tag information by tag ID
     *
     * @param string $tagId DICOM tag ID (e.g., "00100010")
     * @return array<string, mixed>|null Tag information or null if not found
     */
    public function getTagInfo(string $tagId): ?array
    {
        $normalizedTag = DicomTag::normalizeTag($tagId);
        return $this->tagData[$normalizedTag] ?? null;
    }

    /**
     * Get tag name by tag ID
     *
     * @param string $tagId DICOM tag ID (e.g., "00100010")
     * @return string|null Tag name or null if not found
     */
    public function getTagName(string $tagId): ?string
    {
        $normalizedTag = DicomTag::normalizeTag($tagId);
        return $this->tagData[$normalizedTag]['name'] ?? null;
    }

    /**
     * Get tag ID by tag name
     *
     * @param string $name Tag name (e.g., "PatientName")
     * @return string|null Tag ID or null if not found
     */
    public function getTagIdByName(string $name): ?string
    {
        return $this->tagByName[$name] ?? null;
    }

    /**
     * Get tag Value Representation (VR) by tag ID
     *
     * @param string $tagId DICOM tag ID (e.g., "00100010")
     * @return string|null Tag VR or null if not found
     */
    public function getTagVR(string $tagId): ?string
    {
        $normalizedTag = DicomTag::normalizeTag($tagId);
        return $this->tagData[$normalizedTag]['vr'] ?? null;
    }

    /**
     * Get tag description by tag ID
     *
     * @param string $tagId DICOM tag ID (e.g., "00100010")
     * @return string|null Tag description or null if not found
     */
    public function getTagDescription(string $tagId): ?string
    {
        $normalizedTag = DicomTag::normalizeTag($tagId);
        return $this->tagData[$normalizedTag]['description'] ?? null;
    }

    /**
     * Get the meaning of a Value Representation code
     *
     * @param string $vr Value Representation code
     * @return string|null VR meaning or null if unknown
     */
    public function getVRMeaning(string $vr): ?string
    {
        return $this->vrMeanings[strtoupper($vr)] ?? null;
    }

    /**
     * Get all loaded tag definitions
     *
     * @return array<string, array<string, mixed>> Array of tag ID => tag info
     */
    public function getAllTags(): array
    {
        return $this->tagData;
    }

    /**
     * Get all Value Representation codes and their meanings
     *
     * @return array<string, string> Array of VR code => meaning
     */
    public function getAllVRs(): array
    {
        return $this->vrMeanings;
    }
}
