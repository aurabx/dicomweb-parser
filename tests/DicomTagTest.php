<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests;

use Aurabx\DicomWebParser\DicomTag;
use PHPUnit\Framework\TestCase;

class DicomTagTest extends TestCase
{
    protected function setUp(): void
    {
        // Make sure we have tags loaded for testing
        $tagsPath = dirname(__DIR__) . '/resources/tags';

        // If the resources/tags directory doesn't exist, create minimal test data
        if (!is_dir($tagsPath)) {
            $this->createMinimalTagsFile();
            $tagsPath = dirname(__DIR__) . '/resources/tags';
        }

        DicomTag::init($tagsPath);
    }

    /**
     * Create a minimal tags file structure for testing if none exists
     */
    private function createMinimalTagsFile(): void
    {
        $tagsDir = dirname(__DIR__) . '/resources/tags';
        if (!is_dir($tagsDir) && !mkdir($tagsDir, 0755, true) && !is_dir($tagsDir)) {
            $this->markTestSkipped('Could not create tags directory for testing');
        }

        // Create a simple patient.json file with minimal tags
        $patientTags = [
            '00100010' => [
                'name' => 'PatientName',
                'vr' => 'PN',
                'description' => 'Patient\'s full name'
            ],
            '00100020' => [
                'name' => 'PatientID',
                'vr' => 'LO',
                'description' => 'Primary identifier for the patient'
            ]
        ];

        file_put_contents(
            $tagsDir . '/patient.json',
            json_encode($patientTags, JSON_PRETTY_PRINT)
        );

        // Create a simple study.json file with minimal tags
        $studyTags = [
            '0020000D' => [
                'name' => 'StudyInstanceUID',
                'vr' => 'UI',
                'description' => 'Unique identifier for the study'
            ]
        ];

        file_put_contents(
            $tagsDir . '/study.json',
            json_encode($studyTags, JSON_PRETTY_PRINT)
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('PatientName', DicomTag::getName('00100010'));
        $this->assertEquals('PatientName', DicomTag::getName('0010,0010'));
        $this->assertEquals('PatientName', DicomTag::getName('(0010,0010)'));
        $this->assertNull(DicomTag::getName('12345678')); // Unknown tag
    }

    public function testGetTagByName(): void
    {
        $this->assertEquals('00100010', DicomTag::getTagByName('PatientName'));
        $this->assertNull(DicomTag::getTagByName('NonExistentTag'));
    }

    public function testNormalizeTag(): void
    {
        $this->assertEquals('00100010', DicomTag::normalizeTag('00100010'));
        $this->assertEquals('00100010', DicomTag::normalizeTag('0010,0010'));
        $this->assertEquals('00100010', DicomTag::normalizeTag('(0010,0010)'));
        $this->assertEquals('00100000', DicomTag::normalizeTag('0010'));
        $this->assertEquals('ABC', DicomTag::normalizeTag('ABC')); // Invalid but normalized
    }

    public function testFormatTag(): void
    {
        $this->assertEquals('0010,0010', DicomTag::formatTag('00100010', 'comma'));
        $this->assertEquals('(00100010)', DicomTag::formatTag('00100010', 'paren'));
        $this->assertEquals('(0010,0010)', DicomTag::formatTag('00100010', 'both'));
        $this->assertEquals('00100010', DicomTag::formatTag('00100010', 'unknown'));

        // Test with already formatted tags
        $this->assertEquals('0010,0010', DicomTag::formatTag('0010,0010', 'comma'));
        $this->assertEquals('(0010,0010)', DicomTag::formatTag('(0010,0010)', 'both'));
    }

    public function testGetVR(): void
    {
        $this->assertEquals('PN', DicomTag::getVR('00100010'));
        $this->assertEquals('UI', DicomTag::getVR('0020000D'));
        $this->assertNull(DicomTag::getVR('12345678')); // Unknown tag
    }

    public function testGetVRMeaning(): void
    {
        $this->assertEquals('Person Name', DicomTag::getVRMeaning('PN'));
        $this->assertEquals('Unique Identifier', DicomTag::getVRMeaning('UI'));
        $this->assertNull(DicomTag::getVRMeaning('XX')); // Unknown VR
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('Patient\'s full name', DicomTag::getDescription('00100010'));
        $this->assertNull(DicomTag::getDescription('12345678')); // Unknown tag
    }

    public function testIsKnownTag(): void
    {
        $this->assertTrue(DicomTag::isKnownTag('00100010'));
        $this->assertTrue(DicomTag::isKnownTag('0010,0010'));
        $this->assertFalse(DicomTag::isKnownTag('12345678'));
    }

    public function testGetAllTags(): void
    {
        $tags = DicomTag::getAllTags();
        $this->assertIsArray($tags);
        $this->assertNotEmpty($tags);
        $this->assertArrayHasKey('00100010', $tags);
    }

    public function testGetTagInfo(): void
    {
        $tagInfo = DicomTag::getTagInfo('00100010');
        $this->assertIsArray($tagInfo);
        $this->assertEquals('PatientName', $tagInfo['name']);
        $this->assertEquals('PN', $tagInfo['vr']);
        $this->assertNotEmpty($tagInfo['description']);

        $this->assertNull(DicomTag::getTagInfo('12345678')); // Unknown tag
    }
}
