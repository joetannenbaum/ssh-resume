<?php

namespace Chewie\Concerns;

use Laravel\Prompts\Prompt;

trait Ticks
{
    protected int $tickCount = 0;

    public static function make(Prompt $prompt): static
    {
        return new static($prompt);
    }

    public function tick(): void
    {
        $this->onTick();
        $this->tickCount++;
    }

    protected function isNthTick(int $n): bool
    {
        return $this->tickCount % $n === 0;
    }

    protected function onTick(): void
    {
        // Override this method
    }
}
