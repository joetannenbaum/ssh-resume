<?php

namespace Chewie\Components;

use Laravel\Prompts\Concerns\Colors;

class Content
{
    use Colors;

    public function __construct(
        public int $width,
        public int $height,
        public string $content,
    ) {
    }

    public function __toString()
    {
        $lines = collect(explode(PHP_EOL, $this->content))->map(fn ($line) => wordwrap($line, $this->width - 4, PHP_EOL, true));

        return $lines->implode(PHP_EOL);
    }
}
