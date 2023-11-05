<?php

namespace ChewieLab\Dashboard;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use Illuminate\Support\Collection;

class Health implements Tickable
{
    use Ticks;

    public Animatable $value;

    public int $lowerBound = 25;

    public int $upperBound = 75;

    public Collection $ascii;

    public function __construct()
    {
        $this->loadAscii();
        $this->value = Animatable::fromValue(50);
    }

    public function onTick(): void
    {
        if ($this->value->isAnimating()) {
            $this->value->animate();

            return;
        }

        if ($this->isNthTick(10)) {
            $this->value->to($this->generateNext());
        }
    }

    protected function generateNext(): int
    {
        $next = rand($this->lowerBound, $this->upperBound);

        while ($next === $this->value->current()) {
            $next = rand($this->lowerBound, $this->upperBound);
        }

        return $next;
    }

    protected function loadAscii()
    {
        $data = file_get_contents(__DIR__ . '/../../dashboard/health.txt');

        $this->ascii = collect(explode(PHP_EOL, $data))->chunk(3)->map(fn ($lines) => $lines->filter()->values());
    }
}
