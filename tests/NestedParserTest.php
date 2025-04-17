<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests;

use Aurabx\DicomWebParser\Parser;
use PHPUnit\Framework\TestCase;

class NestedParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testNestedSequences(): void
    {
        // JSON with nested sequences (Other Patient IDs Sequence)
        $jsonWithNestedSequence = <<<JSON
[
  {
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
    "00101002": {
      "vr": "SQ",
      "Value": [
        {
          "00100020": {
            "vr": "LO",
            "Value": [
              "ABC-123"
            ]
          },
          "00100021": {
            "vr": "LO",
            "Value": [
              "Hospital A"
            ]
          }
        },
        {
          "00100020": {
            "vr": "LO",
            "Value": [
              "XYZ-789"
            ]
          },
          "00100021": {
            "vr": "LO",
            "Value": [
              "Hospital B"
            ]
          },
          "00100024": {
            "vr": "SQ",
            "Value": [
              {
                "00400031": {
                  "vr": "UT",
                  "Value": [
                    "Universal ID"
                  ]
                },
                "00400032": {
                  "vr": "CS",
                  "Value": [
                    "UUID"
                  ]
                }
              }
            ]
          }
        }
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
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1"
      ]
    }
  }
]
JSON;

        $instance = $this->parser->parseInstance($jsonWithNestedSequence);

        // Test that we have the main patient ID
        $this->assertEquals('12345678', $instance->getFirstValue('00100020'));

        // Test the Other Patient IDs Sequence
        $otherIdsSequence = $instance->getValue('00101002');
        $this->assertIsArray($otherIdsSequence);
        $this->assertCount(2, $otherIdsSequence);

        // Check first other ID item
        $firstOtherId = $otherIdsSequence[0];
        $this->assertIsArray($firstOtherId);
        $this->assertArrayHasKey('00100020', $firstOtherId);
        $this->assertEquals('ABC-123', $firstOtherId['00100020']->getFirstValue());
        $this->assertEquals('Hospital A', $firstOtherId['00100021']->getFirstValue());

        // Check second other ID item
        $secondOtherId = $otherIdsSequence[1];
        $this->assertIsArray($secondOtherId);
        $this->assertArrayHasKey('00100020', $secondOtherId);
        $this->assertEquals('XYZ-789', $secondOtherId['00100020']->getFirstValue());
        $this->assertEquals('Hospital B', $secondOtherId['00100021']->getFirstValue());

        // Check nested sequence inside second other ID item
        $this->assertArrayHasKey('00100024', $secondOtherId);
        $qualifiersSequence = $secondOtherId['00100024']->getValue();
        $this->assertIsArray($qualifiersSequence);
        $this->assertCount(1, $qualifiersSequence);

        // Check nested sequence item
        $qualifierItem = $qualifiersSequence[0];
        $this->assertIsArray($qualifierItem);
        $this->assertArrayHasKey('00400031', $qualifierItem);
        $this->assertEquals('Universal ID', $qualifierItem['00400031']->getFirstValue());
        $this->assertEquals('UUID', $qualifierItem['00400032']->getFirstValue());
    }

    public function testEmptySequences(): void
    {
        // JSON with empty sequence values
        $jsonWithEmptySequence = <<<JSON
[
  {
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
    "00101002": {
      "vr": "SQ"
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
    "00080018": {
      "vr": "UI",
      "Value": [
        "1.2.840.10008.5.1.4.1.1.4.1.123456789.1.1"
      ]
    }
  }
]
JSON;

        $instance = $this->parser->parseInstance($jsonWithEmptySequence);

        // Test that the empty sequence is properly handled
        $otherIdsSequence = $instance->getValue('00101002');
        $this->assertNull($otherIdsSequence);

        // Check that the element exists but has no value
        $otherIdsElement = $instance->getElement('00101002');
        $this->assertNotNull($otherIdsElement);
        $this->assertEquals('SQ', $otherIdsElement->getVR());
        $this->assertFalse($otherIdsElement->hasValue());
    }
}
