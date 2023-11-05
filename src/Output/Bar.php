<?php

declare(strict_types=1);

namespace Chewie\Output;

use Illuminate\Support\Collection;

class Bar
{
    protected array $chars = [
        '▁',
        '▂',
        '▃',
        '▄',
        '▅',
        '▆',
        '▇',
        '█',
    ];

    public function __construct(protected int $value, protected string $orientation = 'vertical')
    {
    }

    public static function vertical(int $value): static
    {
        return new static($value, 'vertical');
    }

    public function asCols(): Collection
    {
        $whole = floor($this->value / count($this->chars));
        $remainder = $this->value % count($this->chars);

        $bars = collect();

        if ($whole > 0) {
            foreach (range(1, $whole) as $i) {
                $bars->push($this->chars[count($this->chars) - 1]);
            }
        }

        if ($remainder > 0) {
            $bars->push($this->chars[$remainder - 1]);
        }

        return $bars->reverse()->values();
    }

    public function asString(): string
    {
        return implode(PHP_EOL, $this->asCols()->toArray());
    }

    public function __toString(): string
    {
        return $this->asString();
    }
}
