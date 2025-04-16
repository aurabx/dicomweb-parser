<?php

namespace Aurabx\DicomWebParser;
/**
 * Utility class for working with DICOM tags
 */
class DicomTag
{
    /**
     * Common DICOM tags with their descriptions
     */
    public const TAGS = [
        // Patient Information
        '00100010' => 'PatientName',
        '00100020' => 'PatientID',
        '00100030' => 'PatientBirthDate',
        '00100040' => 'PatientSex',
        '00101010' => 'PatientAge',
        '00101020' => 'PatientSize',
        '00101030' => 'PatientWeight',

        // Study Information
        '0020000D' => 'StudyInstanceUID',
        '00080020' => 'StudyDate',
        '00080030' => 'StudyTime',
        '00080050' => 'AccessionNumber',
        '00080090' => 'ReferringPhysicianName',
        '00081030' => 'StudyDescription',
        '00081060' => 'NameOfPhysiciansReadingStudy',

        // Series Information
        '0020000E' => 'SeriesInstanceUID',
        '00080060' => 'Modality',
        '00080021' => 'SeriesDate',
        '00080031' => 'SeriesTime',
        '0008103E' => 'SeriesDescription',
        '00200011' => 'SeriesNumber',
        '00185100' => 'PatientPosition',

        // Instance Information
        '00080018' => 'SOPInstanceUID',
        '00080016' => 'SOPClassUID',
        '00200013' => 'InstanceNumber',
        '00080023' => 'ContentDate',
        '00080033' => 'ContentTime',
        '00280010' => 'Rows',
        '00280011' => 'Columns',
        '00280100' => 'BitsAllocated',
        '00280101' => 'BitsStored',
        '00280102' => 'HighBit',
        '00280103' => 'PixelRepresentation',
        '00280004' => 'PhotometricInterpretation',
        '00281050' => 'WindowCenter',
        '00281051' => 'WindowWidth',

        // Image Acquisition
        '00180050' => 'SliceThickness',
        '00180088' => 'SpacingBetweenSlices',
        '00201041' => 'SliceLocation',
        '00080008' => 'ImageType',
        '00200032' => 'ImagePositionPatient',
        '00200037' => 'ImageOrientationPatient',
        '00180080' => 'RepetitionTime',
        '00180081' => 'EchoTime',
        '00180082' => 'InversionTime',
        '00189073' => 'AcquisitionDuration',

        // Protocol
        '00180015' => 'BodyPartExamined',
        '00180020' => 'ScanningSequence',
        '00180021' => 'SequenceVariant',
        '00180022' => 'ScanOptions',
        '00180023' => 'MRAcquisitionType',
        '00180024' => 'SequenceName',
        '00180025' => 'AngioFlag',

        // Miscellaneous
        '00200010' => 'StudyID',
        '00081090' => 'ManufacturerModelName',
        '00080070' => 'Manufacturer',
        '00080080' => 'InstitutionName',
        '00080081' => 'InstitutionAddress',
        '00081040' => 'InstitutionalDepartmentName',
        '00181000' => 'DeviceSerialNumber',
        '00181020' => 'SoftwareVersions',
        '00181030' => 'ProtocolName'
    ];

    /**
     * Map of common tags from name to tag ID
     */
    public const TAG_BY_NAME = [
        'PatientName' => '00100010',
        'PatientID' => '00100020',
        'StudyInstanceUID' => '0020000D',
        'SeriesInstanceUID' => '0020000E',
        'SOPInstanceUID' => '00080018',
        'SOPClassUID' => '00080016',
        'Modality' => '00080060',
        'StudyDate' => '00080020',
        'SeriesNumber' => '00200011',
        'InstanceNumber' => '00200013'
        // Other tags can be added as needed
    ];

    /**
     * Value Representation codes and their meanings
     */
    public const VR_MEANINGS = [
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
     * Get the descriptive name for a tag
     *
     * @param string $tag DICOM tag
     * @return string|null Tag name or null if unknown
     */
    public static function getName(string $tag): ?string
    {
        $normalizedTag = self::normalizeTag($tag);
        return self::TAGS[$normalizedTag] ?? null;
    }

    /**
     * Get the tag ID for a descriptive name
     *
     * @param string $name Tag name
     * @return string|null Tag ID or null if unknown
     */
    public static function getTagByName(string $name): ?string
    {
        return self::TAG_BY_NAME[$name] ?? null;
    }

    /**
     * Normalize a tag by removing any group/element separators
     *
     * @param string $tag DICOM tag (e.g., "0010,0010" or "(0010,0010)")
     * @return string Normalized tag (e.g., "00100010")
     */
    public static function normalizeTag(string $tag): string
    {
        // Remove any non-hexadecimal characters
        $normalized = preg_replace('/[^0-9A-Fa-f]/', '', $tag);

        // Ensure it's 8 characters
        if (strlen($normalized) === 8) {
            return $normalized;
        }

        // If it's 4 characters (group only), add zeros for element
        if (strlen($normalized) === 4) {
            return $normalized . '0000';
        }

        // Return whatever we have (may not be valid)
        return $normalized;
    }

    /**
     * Format a tag with a group/element separator
     *
     * @param string $tag DICOM tag (e.g., "00100010")
     * @param string $format Format specifier ('comma', 'paren', or 'both')
     * @return string Formatted tag (e.g., "0010,0010" or "(0010,0010)")
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
        return self::VR_MEANINGS[strtoupper($vr)] ?? null;
    }

    /**
     * Check if a tag exists in the known tags dictionary
     *
     * @param string $tag DICOM tag
     * @return bool
     */
    public static function isKnownTag(string $tag): bool
    {
        $normalized = self::normalizeTag($tag);
        return isset(self::TAGS[$normalized]);
    }

    /**
     * Get all known tags as an associative array
     *
     * @return array<string, string> Array of tag ID => tag name
     */
    public static function getAllTags(): array
    {
        return self::TAGS;
    }
}
