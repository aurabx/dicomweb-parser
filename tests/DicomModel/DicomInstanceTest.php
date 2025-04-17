<?php

declare(strict_types=1);

namespace Tests\Unit\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\TagNameResolverInterface;
use Aurabx\DicomWebParser\DicomDictionary;
use Aurabx\DicomWebParser\DicomModel\DicomElement;
use Aurabx\DicomWebParser\DicomModel\DicomInstance;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DicomInstanceTest extends TestCase
{
    #[Test]
    public function it_can_add_and_get_elements(): void
    {
        $element = $this->createMock(DicomElement::class);
        $instance = new DicomInstance();

        $instance->addElement('00100010', $element);

        $this->assertSame($element, $instance->getElement('00100010'));
        $this->assertTrue($instance->hasElement('00100010'));
        $this->assertFalse($instance->hasElement('DEADBEEF'));
    }

    #[Test]
    public function it_can_return_values_and_first_values(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getValue')->willReturn(['John Doe']);

        $instance = new DicomInstance();
        $instance->addElement('00100010', $element);

        $this->assertSame(['John Doe'], $instance->getValue('00100010'));
        $this->assertSame('John Doe', $instance->getFirstValue('00100010'));
    }

    #[Test]
    public function it_returns_null_when_element_not_found(): void
    {
        $instance = new DicomInstance();

        $this->assertNull($instance->getElement('99999999'));
        $this->assertNull($instance->getValue('99999999'));
        $this->assertNull($instance->getFirstValue('99999999'));
    }

    #[Test]
    public function it_returns_first_value_by_tag_string(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getValue')->willReturn('ABC123');

        $instance = new DicomInstance();
        $instance->addElement('00100020', $element);

        // Just test with direct tag
        $this->assertSame('ABC123', $instance->getFirstValueByName('00100020'));
    }

    #[Test]
    public function it_exports_to_array_correctly(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getVR')->willReturn('PN');
        $element->method('getValue')->willReturn('John Doe');

        $instance = new DicomInstance();
        $instance->addElement('00100010', $element);

        $this->assertSame([
            '00100010' => [
                'vr' => 'PN',
                'value' => 'John Doe',
            ],
        ], $instance->toArray());
    }

    #[Test]
    public function it_exports_named_array_with_resolver(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getVR')->willReturn('PN');
        $element->method('getValue')->willReturn('John Doe');

        $resolver = $this->createMock(TagNameResolverInterface::class);
        $resolver->method('resolve')->willReturn('PatientName');

        $instance = new DicomInstance(tagNameResolver: $resolver);
        $instance->addElement('00100010', $element);

        $this->assertSame([
            'PatientName' => [
                'vr' => 'PN',
                'value' => 'John Doe',
            ],
        ], $instance->toNamedArray());
    }

    #[Test]
    public function it_exports_flat_array_correctly(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getValue')->willReturn('Some Value');

        $instance = new DicomInstance();
        $instance->addElement('00080060', $element);

        $this->assertSame(['00080060' => 'Some Value'], $instance->toFlatArray());
    }

    #[Test]
    public function it_exports_named_flat_array_with_resolver(): void
    {
        $element = $this->createMock(DicomElement::class);
        $element->method('getValue')->willReturn('MR');

        $resolver = $this->createMock(\Aurabx\DicomWebParser\TagNameResolverInterface::class);
        $resolver->method('resolve')->willReturn('Modality');

        $instance = new DicomInstance(tagNameResolver: $resolver);
        $instance->addElement('00080060', $element);

        $this->assertSame(['Modality' => 'MR'], $instance->toNamedFlatArray());
    }
}
