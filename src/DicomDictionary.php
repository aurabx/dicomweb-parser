<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser;

/**
 * Provides global access to DICOM tag lookup
 */
class DicomDictionary
{
    private static ?DicomTagLoader $loader = null;

    public static function preload(DicomTagLoader $customLoader): void
    {
        self::$loader = $customLoader;
    }

    public static function getLoader(): DicomTagLoader
    {
        if (!self::$loader) {
            self::$loader = new DicomTagLoader();
        }

        return self::$loader;
    }

    public static function getTagIdByName(string $name): ?string
    {
        return self::getLoader()->getTagIdByName($name);
    }

    public static function getTagName(string $tagId): ?string
    {
        return self::getLoader()->getTagName($tagId);
    }

    public static function getTagInfo(string $tagId): ?array
    {
        return self::getLoader()->getTagInfo($tagId);
    }

    public static function getTagVR(string $tagId): ?string
    {
        return self::getLoader()->getTagVR($tagId);
    }

    public static function getTagDescription(string $tagId): ?string
    {
        return self::getLoader()->getTagDescription($tagId);
    }

    public static function getVRMeaning(string $vr): ?string
    {
        return self::getLoader()->getVRMeaning($vr);
    }

    public static function getAllTags(): array
    {
        return self::getLoader()->getAllTags();
    }

    public static function getAllVRs(): array
    {
        return self::getLoader()->getAllVRs();
    }
}
