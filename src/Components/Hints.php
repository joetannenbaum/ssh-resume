<?php

namespace Chewie\Components;

class Hints
{
    public function __construct(
        public int $availableWidth,
        public int $availableHeight,
        public array $hints,
    ) {
    }

    public function __toString()
    {
        return implode(str_repeat(' ', 4), $this->hints) . PHP_EOL;
    }
}
