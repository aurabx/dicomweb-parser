<?php

declare(strict_types=1);

namespace Aurabx\DicomWebParser\Tests\Unit\DicomModel;

use Aurabx\DicomWebParser\DicomModel\DicomElement;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use Aurabx\DicomWebParser\ParserOptions;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DicomInstanceTest extends TestCase
{
    #[Test]
    public function it_can_add_and_get_elements(): void
    {
        $element = new DicomElement('00100010', 'PN', ['John Doe']);
        $instance = new DicomInstance();

        $instance->addElement('00100010', $element);

        $this->assertSame($element, $instance->getElement('00100010'));
        $this->assertTrue($instance->hasElement('00100010'));
        $this->assertFalse($instance->hasElement('DEADBEEF'));
    }

    #[Test]
    public function it_can_return_values_and_first_values(): void
    {
        $element = new DicomElement('0020000D', 'UI', ['1.2.3.4.5']);

        $instance = new DicomInstance();
        $instance->addElement('0020000D', $element);

        $this->assertSame('1.2.3.4.5', $instance->getElementValue('0020000D'));
        $this->assertSame('1.2.3.4.5', $instance->getElementFirstValue('0020000D'));
    }

    #[Test]
    public function it_returns_null_when_element_not_found(): void
    {
        $instance = new DicomInstance();

        $this->assertNull($instance->getElement('99999999'));
        $this->assertNull($instance->getElementValue('99999999'));
        $this->assertNull($instance->getElementFirstValue('99999999'));
    }

    #[Test]
    public function it_returns_first_value_by_tag_string(): void
    {
        $element = new DicomElement('00100020', 'LO', ['ABC123']);

        $instance = new DicomInstance();
        $instance->addElement('00100020', $element);

        $this->assertSame('ABC123', $instance->getElementFirstValueByKeyword('00100020'));
    }

    #[Test]
    public function it_exports_to_array_correctly(): void
    {
        $element = new DicomElement('00100020', 'LO', ['ABC123']);

        $instance = new DicomInstance();
        $instance->addElement('00100020', $element);

        $result = $instance->toArray();

        $this->assertSame([
            '00100020' => 'ABC123',
        ], $result);
    }

    #[Test]
    public function it_exports_flat_array_correctly(): void
    {
        $element = new DicomElement('00080060', 'CS', ['CT']);

        $instance = new DicomInstance();
        $instance->addElement('00080060', $element);

        $this->assertSame(['Modality' => 'CT'], $instance->toArray(ParserOptions::USE_KEYWORDS));
    }
}
