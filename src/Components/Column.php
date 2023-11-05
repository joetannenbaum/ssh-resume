<?php

namespace Chewie\Components;

use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;

class Column
{
    use DrawsBoxes;

    public string $content;

    public int $width;

    public function __construct(
        public int $availableWidth,
        public int $availableHeight,
        public int $percentageWidth,
        callable $cb,
    ) {
        $this->width = (int) floor($this->availableWidth * ($this->percentageWidth / 100));
        $this->content = $cb($this->width, $this->availableHeight);
    }

    public function __toString()
    {
        $lines = collect(explode(PHP_EOL, $this->content))
            ->map(fn ($line) => $line . str_repeat(' ', max($this->width - mb_strlen($this->stripEscapeSequences($line)), 0)));

        $lines->prepend(str_repeat(' ', $this->width));

        while ($lines->count() < $this->availableHeight) {
            $lines->push(str_repeat(' ', $this->width));
        }

        return $lines->join(PHP_EOL);
    }
}
