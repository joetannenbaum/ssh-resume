<?php

namespace Chewie\Components;

class Row
{
    public string $content;

    public int $width;

    public function __construct(
        public int $availableWidth,
        public int $availableHeight,
        public array $children,
    ) {
    }

    public function __toString()
    {
        $components = collect($this->children)->map(fn ($child) => $child(
            $this->availableWidth,
            $this->availableHeight
        ))->map(fn ($comp) => explode(PHP_EOL, $comp));

        $lines = collect($components->shift())->zip(...$components)->map(fn ($args) => $args->implode(''));

        return $lines->implode(PHP_EOL);
    }
}
