<?php

namespace Chewie\Components;

use Laravel\Prompts\Concerns\Colors;

class Hint
{
    use Colors;

    public function __construct(
        public string $key,
        public string $description,
        public bool $enabled = true,
    ) {
    }

    public function __toString()
    {
        if ($this->enabled) {
            return $this->key . ' ' . $this->dim($this->description);
        }

        return $this->dim("{$this->key} {$this->description}");
    }
}
