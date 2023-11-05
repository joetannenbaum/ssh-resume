<?php

namespace ChewieLab\Prong;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use ChewieLab\Prong;

class Ball implements Tickable
{
    use Ticks;

    public int $y;

    public int $x = 0;

    public int $direction;

    protected array $steps = [];

    public function __construct(protected Prong $prompt)
    {
        // Pick a random side to start on
        $this->x = rand(0, 1) === 0 ? 0 : $this->prompt->width - 2;
    }

    public function onTick(): void
    {
        if (count($this->steps) === 0) {
            $this->prompt->determineWinner();
            $this->start();
        }

        $this->y = array_shift($this->steps);
        $this->x += $this->direction;
    }

    public function start()
    {
        $this->y ??= rand(0, $this->prompt->height);

        $nextY = rand(0, $this->prompt->height);

        $steps = range($this->y, $nextY);

        $i = 0;

        while (count($steps) < $this->prompt->width - 2) {
            $steps[] = $steps[$i];
            $i++;
        }

        sort($steps);

        if ($nextY < $this->y) {
            $steps = array_reverse($steps);
        }

        $this->steps = $steps;

        $this->direction = $this->x === 0 ? 1 : -1;
    }
}
