<?php

declare(strict_types=1);

namespace Tests\Unit\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\DicomModel\DicomTag;
use Aurabx\DicomWebParser\DicomTagLoader;
use Aurabx\DicomWebParser\ParserException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DicomTagTest extends TestCase
{
    private function injectLoader(\PHPUnit\Framework\MockObject\MockObject $mock): void
    {
        $reflection = new ReflectionClass(DicomTag::class);
        $property = $reflection->getProperty('loader');
        $property->setAccessible(true);
        $property->setValue(null, $mock);
    }

    #[Test]
    public function it_returns_tag_name_from_loader(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getTagName')->with('00100010')->willReturn('PatientName');

        $this->injectLoader($loader);

        $this->assertSame('PatientName', DicomTag::getName('00100010'));
    }

    #[Test]
    public function it_returns_tag_id_from_loader(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getTagIdByName')->with('PatientName')->willReturn('00100010');

        $this->injectLoader($loader);

        $this->assertSame('00100010', DicomTag::getTagByName('PatientName'));
    }

    #[Test]
    public function it_returns_tag_vr_from_loader(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getTagVR')->with('00100010')->willReturn('PN');

        $this->injectLoader($loader);

        $this->assertSame('PN', DicomTag::getVR('00100010'));
    }

    #[Test]
    public function it_formats_tags_correctly(): void
    {
        $this->assertSame('0010,0010', DicomTag::formatTag('00100010', 'comma'));
        $this->assertSame('(00100010)', DicomTag::formatTag('00100010', 'paren'));
        $this->assertSame('(0010,0010)', DicomTag::formatTag('00100010', 'both'));
    }

    #[Test]
    public function it_normalizes_tags_correctly(): void
    {
        $this->assertSame('00100010', DicomTag::normalizeTag('(0010,0010)'));
        $this->assertSame('00100010', DicomTag::normalizeTag('0010,0010'));
        $this->assertSame('00100000', DicomTag::normalizeTag('0010'));
    }

    #[Test]
    public function it_throws_on_invalid_tag_input(): void
    {
        $this->expectException(ParserException::class);
        $this->expectExceptionMessage('Invalid DICOM tag: ZXY123');

        DicomTag::normalizeTag('ZXY123');
    }

    #[Test]
    public function it_returns_vr_meaning(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getVRMeaning')->with('PN')->willReturn('Person Name');

        $this->injectLoader($loader);

        $this->assertSame('Person Name', DicomTag::getVRMeaning('PN'));
    }

    #[Test]
    public function it_checks_known_tags(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getTagName')->with('00100010')->willReturn('PatientName');

        $this->injectLoader($loader);

        $this->assertTrue(DicomTag::isKnownTag('00100010'));
    }

    #[Test]
    public function it_returns_all_tags(): void
    {
        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getAllTags')->willReturn([
            '00100010' => ['name' => 'PatientName', 'vr' => 'PN']
        ]);

        $this->injectLoader($loader);

        $tags = DicomTag::getAllTags();
        $this->assertArrayHasKey('00100010', $tags);
        $this->assertSame('PatientName', $tags['00100010']['name']);
    }

    #[Test]
    public function it_returns_tag_info(): void
    {
        $info = ['name' => 'PatientName', 'vr' => 'PN'];

        $loader = $this->createMock(DicomTagLoader::class);
        $loader->method('getTagInfo')->with('00100010')->willReturn($info);

        $this->injectLoader($loader);

        $this->assertSame($info, DicomTag::getTagInfo('00100010'));
    }
}
