<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomTagLoader;
use Aurabx\DicomWebParser\ParserException;

/**
 * Utility class for working with DICOM tags
 */
class DicomTag
{
    /**
     * @var DicomTagLoader|null Tag loader instance
     */
    private static ?DicomTagLoader $loader = null;

    /**
     * Initialize the DicomTag class with a specific tags file or directory
     *
     * @param  string  $tagsPath  Path to the tags JSON file or directory
     * @return void
     */
    public static function init(string $tagsPath): void
    {
        // Create a new loader with the specified path
        self::$loader = null; // Reset any existing loader
        self::getLoader(); // This will create a new loader with the custom path
    }

    /**
     * Get or create the tag loader instance
     *
     * @return DicomTagLoader
     */
    private static function getLoader(): DicomTagLoader
    {
        if (self::$loader === null) {
            self::$loader = new DicomTagLoader();
        }

        return self::$loader;
    }

    /**
     * Get the descriptive name for a tag
     *
     * @param string $tag DICOM tag
     * @return string|null Tag name or null if unknown
     */
    public static function getName(string $tag): ?string
    {
        return self::getLoader()->getTagName($tag);
    }

    /**
     * Get the tag ID for a descriptive name
     *
     * @param string $name Tag name
     * @return string|null Tag ID or null if unknown
     */
    public static function getTagByName(string $name): ?string
    {
        return self::getLoader()->getTagIdByName($name);
    }

    /**
     * Get the Value Representation (VR) for a tag
     *
     * @param string $tag DICOM tag
     * @return string|null VR code or null if unknown
     */
    public static function getVR(string $tag): ?string
    {
        return self::getLoader()->getTagVR($tag);
    }

    /**
     * Get the description for a tag
     *
     * @param string $tag DICOM tag
     * @return string|null Description or null if unknown
     */
    public static function getDescription(string $tag): ?string
    {
        return self::getLoader()->getTagDescription($tag);
    }

    /**
     * Normalize a tag by removing any group/element separators
     *
     * @param string $tag DICOM tag (e.g., "0010,0010" or "(0010,0010)")
     * @return string Normalized tag (e.g., "00100010")
     */
    public static function normalizeTag(string $tag): string
    {
        $normalized = preg_replace('/[^0-9A-Fa-f]/', '', $tag);

        if (strlen($normalized) === 4) {
            $normalized .= '0000';
        }

        if (strlen($normalized) !== 8) {
            throw new ParserException("Invalid DICOM tag: $tag");
        }

        return strtoupper($normalized);
    }

    /**
     * Format a tag with a group/element separator
     *
     * @param  string  $tag  DICOM tag (e.g., "00100010")
     * @param  string  $format  Format specifier ('comma', 'paren', or 'both')
     * @return string Formatted tag (e.g., "0010,0010" or "(0010,0010)")
     * @throws ParserException
     */
    public static function formatTag(string $tag, string $format = 'comma'): string
    {
        $normalized = self::normalizeTag($tag);

        if (strlen($normalized) !== 8) {
            return $normalized;
        }

        $group = substr($normalized, 0, 4);
        $element = substr($normalized, 4, 4);

        switch ($format) {
            case 'comma':
                return $group . ',' . $element;
            case 'paren':
                return '(' . $group . $element . ')';
            case 'both':
                return '(' . $group . ',' . $element . ')';
            default:
                return $normalized;
        }
    }

    /**
     * Get the meaning of a Value Representation code
     *
     * @param string $vr Value Representation code
     * @return string|null VR meaning or null if unknown
     */
    public static function getVRMeaning(string $vr): ?string
    {
        return self::getLoader()->getVRMeaning($vr);
    }

    /**
     * Check if a tag exists in the known tags dictionary
     *
     * @param string $tag DICOM tag
     * @return bool
     */
    public static function isKnownTag(string $tag): bool
    {
        return self::getLoader()->getTagName($tag) !== null;
    }

    /**
     * Get all known tags as an associative array
     *
     * @return array<string, array<string, mixed>> Array of tag ID => tag info
     */
    public static function getAllTags(): array
    {
        return self::getLoader()->getAllTags();
    }

    /**
     * Get complete information about a tag
     *
     * @param string $tag DICOM tag
     * @return array<string, mixed>|null Tag information or null if unknown
     */
    public static function getTagInfo(string $tag): ?array
    {
        return self::getLoader()->getTagInfo($tag);
    }
}
