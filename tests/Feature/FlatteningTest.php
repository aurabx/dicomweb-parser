<?php

namespace Aurabx\DicomWebParser\Tests\Feature;

use Aurabx\DicomWebParser\ParserOptions;
use Aurabx\DicomWebParser\Tests\HasTestData;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class FlatteningTest extends TestCase
{
    use HasTestData;

    #[Test]
    public function it_returns_named_flat_array(): void
    {
        $parser = new \Aurabx\DicomWebParser\Parser();

        $study = $parser->parseStudy($this->getTestData());

        $flattened = $study->toArray(ParserOptions::USE_KEYWORDS);

        $this->assertArrayHasKey('StudyDate', $flattened);
        $this->assertIsArray($flattened['ReferringPhysicianName']);
    }
}
