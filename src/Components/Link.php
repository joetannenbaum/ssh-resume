<?php

namespace Chewie\Components;

use Laravel\Prompts\Concerns\Colors;

class Link
{
    use Colors;

    public function __construct(
        public int $width,
        public int $height,
        public string $label,
        public bool $enabled = true,
    ) {
    }

    public function __toString()
    {
        $this->label = ' ' . mb_str_pad($this->label, $this->width - 14);

        if ($this->enabled) {
            return $this->bgCyan($this->black($this->label));
        }

        return $this->dim($this->label);
    }
}
