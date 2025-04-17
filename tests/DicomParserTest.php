<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests;

use Aurabx\DicomWebParser\DicomDictionary;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\DicomModel\DicomStudy;
use Aurabx\DicomWebParser\DicomTagLoader;
use Aurabx\DicomWebParser\Parser;
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
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789', $instance->getFirstValue('0020000D'));
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1', $instance->getFirstValue('0020000E'));
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1', $instance->getFirstValue('00080018'));
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4', $instance->getFirstValue('00080016'));
        $this->assertEquals('MR', $instance->getFirstValue('00080060'));
        $this->assertEquals('12345678', $instance->getFirstValue('00100020'));

        $patientName = $instance->getFirstValue('00100010');
        $this->assertIsArray($patientName);
        $this->assertEquals('Doe', $patientName['Alphabetic']['FamilyName']);
        $this->assertEquals('Jane', $patientName['Alphabetic']['GivenName']);
    }

    public function testParseStudy(): void
    {
        $study = $this->parser->parseStudy($this->sampleJson);

        $this->assertInstanceOf(DicomStudy::class, $study);
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789', $study->getFirstValue('0020000D'));
        $this->assertEquals(1, $study->getSeriesCount());
        $this->assertEquals(1, $study->getTotalInstanceCount());
        $this->assertEquals(['MR'], $study->getModalities());
        $this->assertEquals('12345678', $study->getFirstValue('00100020'));
        $this->assertEquals('BRAIN STUDY', $study->getFirstValue('00081030'));

        $seriesList = $study->getSeries();
        $this->assertCount(1, $seriesList);

        $series = $seriesList[0];
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1', $series->getFirstValue('0020000E'));
        $this->assertEquals(1, $series->getInstanceCount());
        $this->assertEquals('MR', $series->getFirstValue('00080060'));
        $this->assertEquals(1, $series->getFirstValue('00200011'));

        $instances = $series->getInstances();
        $this->assertCount(1, $instances);
        $this->assertEquals('1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1', $instances[0]->getFirstValue('00080018'));
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

    public function testParseStudyWithMultipleSeries(): void
    {
        $studyInstanceUid = '1.2.3.4.5.6.7.8.9';

        $metadata = [
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Brain CT and MRI Study']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['120000']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Dr. John Smith']]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Smith^John']]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-1']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['Axial CT']],
                '00080060' => ['vr' => 'CS', 'Value' => ['CT']],
                '00200011' => ['vr' => 'IS', 'Value' => ['2']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ],
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Brain CT and MRI Study']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['120000']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Dr. John Smith']]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Smith^John']]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-2']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['Sagittal MRI']],
                '00080060' => ['vr' => 'CS', 'Value' => ['MR']],
                '00200011' => ['vr' => 'IS', 'Value' => ['3']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ],
            [
                '0020000D' => ['vr' => 'UI', 'Value' => [$studyInstanceUid]],
                '00081030' => ['vr' => 'LO', 'Value' => ['Complete Neurological Examination']],
                '00080020' => ['vr' => 'DA', 'Value' => ['20240101']],
                '00080030' => ['vr' => 'TM', 'Value' => ['115500']],
                '00080050' => ['vr' => 'SH', 'Value' => ['ACC123456']],
                '00080090' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Dr. Jane Miller']]],
                '00080080' => ['vr' => 'LO', 'Value' => ['Test Hospital']],
                '00080081' => ['vr' => 'ST', 'Value' => ['123 Medical Drive, Boston MA 02115']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PatientID123']],
                '00100010' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Smith^John']]],
                '00100040' => ['vr' => 'CS', 'Value' => ['M']],
                '00100030' => ['vr' => 'DA', 'Value' => ['19800101']],
                '00101010' => ['vr' => 'AS', 'Value' => ['45']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['series-uid-3']],
                '0008103E' => ['vr' => 'LO', 'Value' => ['X-Ray']],
                '00080060' => ['vr' => 'CS', 'Value' => ['CR']],
                '00200011' => ['vr' => 'IS', 'Value' => ['1']],
                '00180015' => ['vr' => 'CS', 'Value' => ['HEAD']],
            ]
        ];

        $study = $this->parser->parseStudy($metadata);

        $this->assertInstanceOf(\Aurabx\DicomWebParser\DicomModel\DicomStudy::class, $study);
        $this->assertEquals($studyInstanceUid, $study->getStudyInstanceUid());
        $this->assertEquals(3, $study->getSeriesCount());
        $this->assertEquals(['CT', 'MR', 'CR'], array_values(array_unique($study->getModalities())));
        $this->assertEquals('PatientID123', $study->getFirstValue('00100020'));

        $series = $study->getSeries();
        $this->assertCount(3, $series);

        // Sort by series number to assert order
        usort($series, fn($a, $b) => $a->getSeriesNumber() <=> $b->getSeriesNumber());

        $this->assertEquals('series-uid-3', $series[0]->getSeriesInstanceUid()); // Series number 1
        $this->assertEquals('series-uid-1', $series[1]->getSeriesInstanceUid()); // Series number 2
        $this->assertEquals('series-uid-2', $series[2]->getSeriesInstanceUid()); // Series number 3

        echo print_r($study);
    }

    public function testComplexOtherPatientIdsSequence(): void
    {
        $metadata = [
            [
                '0020000D' => ['vr' => 'UI', 'Value' => ['1.2.3']],
                '0020000E' => ['vr' => 'UI', 'Value' => ['1.2.3.4']],
                '00080018' => ['vr' => 'UI', 'Value' => ['1.2.3.4.5']],
                '00100020' => ['vr' => 'LO', 'Value' => ['PrimaryID']],
                '0010000B' => [
                    'vr' => 'SQ',
                    'Value' => [
                        [
                            '00100020' => ['vr' => 'LO', 'Value' => ['AltID1']],
                            '00100021' => ['vr' => 'LO', 'Value' => ['HospitalA']],
                            '00100022' => ['vr' => 'CS', 'Value' => ['TEXT']]
                        ],
                        [
                            '00100020' => ['vr' => 'LO', 'Value' => ['AltID2']],
                            '00100024' => ['vr' => 'SQ', 'Value' => [
                                [
                                    '00400032' => ['vr' => 'LO', 'Value' => ['NationalID']],
                                    '00400033' => ['vr' => 'LO', 'Value' => ['AU']]
                                ]
                            ]]
                        ],
                        [
                            '00100020' => ['vr' => 'LO', 'Value' => ['AltID3']]
                        ]
                    ]
                ]
            ]
        ];

        $instance = $this->parser->parseInstance($metadata);
        $this->assertInstanceOf(\Aurabx\DicomWebParser\DicomModel\DicomInstance::class, $instance);

        $otherIds = $instance->getValue('0010000B');
        $this->assertCount(3, $otherIds);

        // Validate first item
        $item1 = $otherIds[0];
        $this->assertEquals('AltID1', $item1['00100020']->getFirstValue());
        $this->assertEquals('HospitalA', $item1['00100021']->getFirstValue());
        $this->assertEquals('TEXT', $item1['00100022']->getFirstValue());

        // Validate second item
        $item2 = $otherIds[1];
        $this->assertEquals('AltID2', $item2['00100020']->getFirstValue());
        $this->assertIsArray($item2['00100024']->getValue());
        $this->assertEquals('NationalID', $item2['00100024']->getValue()[0]['00400032']->getFirstValue());
        $this->assertEquals('AU', $item2['00100024']->getValue()[0]['00400033']->getFirstValue());

        // Validate third item
        $item3 = $otherIds[2];
        $this->assertEquals('AltID3', $item3['00100020']->getFirstValue());
    }

    public function testGetValueByName(): void
    {
        // Preload minimal dictionary
        $loader = new DicomTagLoader();
        $loader->loadFromArray([
            '00100020' => ['name' => 'PatientID', 'vr' => 'LO'],
            '00180084' => ['name' => 'ImagingFrequency', 'vr' => 'DS'],
        ]);

        DicomDictionary::preload($loader); // ensure global lookup works

        $metadata = [[
            '00100010' => ['vr' => 'PN', 'Value' => [['Alphabetic' => 'Doe^Jane']]],
            '00100020' => ['vr' => 'LO', 'Value' => ['P123']],
            '00180084' => ['vr' => 'DS', 'Value' => ['63.87']],
            '0020000D' => ['vr' => 'UI', 'Value' => ['1.2.3.4']],
            '0020000E' => ['vr' => 'UI', 'Value' => ['1.2.3.4.1']],
            '00080018' => ['vr' => 'UI', 'Value' => ['1.2.3.4.1.1']],
        ]];


        $instance = $this->parser->parseInstance($metadata);

        $this->assertSame('63.87', $instance->getFirstValueByName('ImagingFrequency'));
        $this->assertSame('P123', $instance->getFirstValueByName('PatientID'));
    }

}
