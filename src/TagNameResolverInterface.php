<?php

namespace Aurabx\DicomWebParser;

interface TagNameResolverInterface
{
    public function resolve(string $tag): string;

    public function getTagIdByName(string $name): ?string;

}
