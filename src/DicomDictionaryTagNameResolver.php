<?php

namespace Aurabx\DicomWebParser;

final class DicomDictionaryTagNameResolver implements TagNameResolverInterface
{
    public function resolve(string $tag): string
    {
        return DicomDictionary::getTagName($tag);
    }

    public function getTagIdByName(string $name): ?string
    {
        return DicomDictionary::getTagIdByName($name);
    }
}
