<?php

namespace Aurabx\DicomWebParser\DicomModel;

use Aurabx\DicomWebParser\ParserOptions;

class DicomSequence
{
    public string $tag;

    public function __construct(
        string $tag,
    ) {
        $this->tag = $tag;
    }

    /**
     * @var array<DicomSequenceItem>
     */
    private array $items = [];

    /**
     * @param  DicomSequenceItem  $item
     * @return void
     */
    public function addSequenceItem(DicomSequenceItem $item): void
    {
        $this->items[] = $item;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param  string|int  $index
     * @return DicomSequenceItem|null
     */
    public function getItem(string|int $index): ?DicomSequenceItem
    {
        return $this->items[(int) $index] ?? null;
    }

    /**
     * @param  string  $keys
     * @return array
     */
    public function toArray(string $keys = ParserOptions::USE_TAGS): array
    {
        if (empty($this->items)) {
            return [];
        }

        $items = [];
        foreach ($this->items as $item) {
            $items[] = $item->toArray($keys);
        }

        return $items;
    }
}
