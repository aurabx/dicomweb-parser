<?php

namespace Aurabx\DicomWebParser\Elements;

interface ElementParserInterface
{
    public static function parse(array $element): array;
}
