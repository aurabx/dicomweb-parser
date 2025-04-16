<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests;

use Aurabx\DicomWebParser\Parser;
use Aurabx\DicomWebParser\DicomInstance;
use Aurabx\DicomWebParser\DicomStudy;
use Aurabx\DicomWebParser\ParserException;
use PHPUnit\Framework\TestCase;

class DicomParserTest extends TestCase
{
    private string $sampleJson;
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();

        // Sample JSON representing a DICOM instance
        $this->sampleJson = <<<JSON
[
  {
    "00080005": {
      "vr": "CS",
      "Value": [
        "ISO_IR 100"
      ]
    },
    "00080020": {
      "vr": "DA",
      "Value": [
        "20230514"
      ]
    },
    "00080060": {
      "vr": "CS",
      "Value": [
        "MR"
      ]
    },
    "00081030": {
      "vr": "LO",
      "Value": [
        "BRAIN STUDY"
      ]
    },
    "00100010": {
      "vr": "PN",
      "Value": [
        {
          "Alphabetic": {
            "FamilyName": "Doe",
            "GivenName": "Jane"
          }
        }
      ]
    },
    "00100020": {
      "vr": "LO",
      "Value": [
        "12345678"
      ]
    },
    "0020000D": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789"
      ]
    },
    "0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1"
      ]
    },
    "00200011": {
      "vr": "IS",
      "Value": [
        1
      ]
    },
    "00200013": {
      "vr": "IS",
      "Value": [
        1
      ]
    },
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1"
      ]
    },
    "00080016": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4"
      ]
    }
  }
]
JSON;
    }

    public function testParseInstance(): void
    {
        $instance = $this->parser->parseInstance($this->sampleJson);

        $this->assertInstanceOf(DicomInstance::class, $instance);
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789', $instance->getStudyInstanceUid());
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1', $instance->getSeriesInstanceUid());
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1', $instance->getSopInstanceUid());
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4', $instance->getSopClassUid());
        $this->assertEquals('MR', $instance->getModality());
        $this->assertEquals('12345678', $instance->getFirstValue('00100020'));

        // Test patient name
        $patientName = $instance->getFirstValue('00100010');
        $this->assertIsArray($patientName);
        $this->assertEquals('Doe', $patientName['family']);
        $this->assertEquals('Jane', $patientName['given']);
    }

    public function testParseStudy(): void
    {
        $study = $this->parser->parseStudy($this->sampleJson);

        $this->assertInstanceOf(DicomStudy::class, $study);
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789', $study->getStudyInstanceUid());
        $this->assertEquals(1, $study->getSeriesCount());
        $this->assertEquals(1, $study->getTotalInstanceCount());
        $this->assertEquals(['MR'], $study->getModalities());
        $this->assertEquals('12345678', $study->getPatientId());
        $this->assertEquals('BRAIN STUDY', $study->getStudyDescription());

        // Test series
        $seriesList = $study->getSeries();
        $this->assertCount(1, $seriesList);

        $series = $seriesList[0];
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1', $series->getSeriesInstanceUid());
        $this->assertEquals(1, $series->getInstanceCount());
        $this->assertEquals('MR', $series->getModality());
        $this->assertEquals(1, $series->getSeriesNumber());

        // Test instances in series
        $instances = $series->getInstances();
        $this->assertCount(1, $instances);
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1', $instances[0]->getSopInstanceUid());
    }

    public function testParseInvalidJson(): void
    {
        $this->expectException(ParserException::class);
        $this->parser->parseInstance('invalid json');
    }

    public function testParseEmptyJson(): void
    {
        $this->expectException(ParserException::class);
        $this->parser->parseInstance('[]');
    }

    public function testDateParsing(): void
    {
        $instance = $this->parser->parseInstance($this->sampleJson);
        $studyDate = $instance->getFirstValue('00080020');

        // The date should be parsed as a DateTimeImmutable
        if ($studyDate instanceof \DateTimeImmutable) {
            $this->assertEquals('2023-05-14', $studyDate->format('Y-m-d'));
        } else {
            // If we're not automatically converting to DateTimeImmutable
            $this->assertEquals('20230514', $studyDate);
        }
    }

    public function testParseMultipleInstances(): void
    {
        // Create JSON with multiple instances
        $multiJson = str_replace(
            '"00200013": {
      "vr": "IS",
      "Value": [
        1
      ]
    }',
            '"00200013": {
      "vr": "IS",
      "Value": [
        1
      ]
    }
  },
  {
    "00080060": {
      "vr": "CS",
      "Value": [
        "MR"
      ]
    },
    "0020000D": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789"
      ]
    },
    "0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1"
      ]
    },
    "00200013": {
      "vr": "IS",
      "Value": [
        2
      ]
    }',
            $this->sampleJson
        );

        $instances = $this->parser->parseInstances($multiJson);
        $this->assertCount(2, $instances);

        $study = $this->parser->parseStudy($multiJson);
        $this->assertEquals(1, $study->getSeriesCount());
        $this->assertEquals(2, $study->getTotalInstanceCount());
    }

    public function testParseDifferentSeries(): void
    {
        // Create JSON with multiple instances in different series
        $multiSeriesJson = str_replace(
            '"0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1"
      ]
    }',
            '"0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.2"
      ]
    }',
            $this->sampleJson
        );

        // Combine the original and modified JSON
        $multiSeriesJson = substr($multiSeriesJson, 0, -1) . ',' . substr($this->sampleJson, 1);

        $study = $this->parser->parseStudy($multiSeriesJson);
        $this->assertEquals(2, $study->getSeriesCount());
        $this->assertEquals(2, $study->getTotalInstanceCount());

        // Check that the series have different UIDs
        $seriesList = $study->getSeries();
        $this->assertNotEquals(
            $seriesList[0]->getSeriesInstanceUid(),
            $seriesList[1]->getSeriesInstanceUid()
        );
    }

    public function testSequenceVR(): void
    {
        // JSON with a sequence VR
        $jsonWithSequence = <<<JSON
[
  {
    "00400275": {
      "vr": "SQ",
      "Value": [
        {
          "00400009": {
            "vr": "SH",
            "Value": [
              "SCHEDULED"
            ]
          },
          "00400020": {
            "vr": "CS",
            "Value": [
              "READY"
            ]
          }
        },
        {
          "00400009": {
            "vr": "SH",
            "Value": [
              "ARRIVED"
            ]
          },
          "00400020": {
            "vr": "CS",
            "Value": [
              "READY"
            ]
          }
        }
      ]
    },
    "0020000D": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5"
      ]
    },
    "0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5.1"
      ]
    },
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5.1.1"
      ]
    }
  }
]
JSON;

        $instance = $this->parser->parseInstance($jsonWithSequence);
        $sequence = $instance->getValue('00400275');

        $this->assertIsArray($sequence);
        $this->assertCount(2, $sequence);

        // Check the first item in the sequence
        $firstItem = $sequence[0];
        $this->assertIsArray($firstItem);
        $this->assertArrayHasKey('00400009', $firstItem);
        $this->assertEquals('SCHEDULED', $firstItem['00400009']->getFirstValue());

        // Check the second item in the sequence
        $secondItem = $sequence[1];
        $this->assertIsArray($secondItem);
        $this->assertArrayHasKey('00400009', $secondItem);
        $this->assertEquals('ARRIVED', $secondItem['00400009']->getFirstValue());
    }

    public function testMultiValuedElement(): void
    {
        // JSON with a multi-valued element
        $jsonWithMultiValues = <<<JSON
[
  {
    "00081032": {
      "vr": "SQ",
      "Value": [
        {
          "00080100": {
            "vr": "SH",
            "Value": [
              "CODE1",
              "CODE2",
              "CODE3"
            ]
          }
        }
      ]
    },
    "0020000D": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5"
      ]
    },
    "0020000E": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5.1"
      ]
    },
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.3.4.5.1.1"
      ]
    }
  }
]
JSON;

        $instance = $this->parser->parseInstance($jsonWithMultiValues);
        $procedureCodeSequence = $instance->getValue('00081032');

        $this->assertIsArray($procedureCodeSequence);
        $firstItem = $procedureCodeSequence[0];
        $codes = $firstItem['00080100']->getValue();

        $this->assertIsArray($codes);
        $this->assertCount(3, $codes);
        $this->assertEquals('CODE1', $codes[0]);
        $this->assertEquals('CODE2', $codes[1]);
        $this->assertEquals('CODE3', $codes[2]);
    }
}
