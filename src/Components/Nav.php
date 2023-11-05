<?php

namespace Chewie\Components;

class Nav
{
    public function __construct(
        public int $availableWidth,
        public int $availableHeight,
        public array $items,
    ) {
    }

    public function __toString()
    {
        return implode(
            PHP_EOL,
            array_map(
                fn ($item) => $item($this->availableWidth, $this->availableHeight),
                $this->items
            )
        );
    }
}
