<?php

namespace Aurabx\DicomWebParser\Elements;

class FloatingPointParser implements ElementParserInterface
{

    public static function parse(array $element): mixed
    {
        return $element['Value'];
    }
}
