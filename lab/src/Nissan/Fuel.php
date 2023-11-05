<?php

namespace ChewieLab\Nissan;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use ChewieLab\Nissan;

class Fuel implements Tickable
{
    use Ticks;

    public Animatable $value;

    public $revCount = 0;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(13)->pauseAfter(20);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function startCar()
    {
        $this->value->to(13);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 7 === 0) {
            $this->value->toRelative(-1);
        }
    }
}
