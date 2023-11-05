<?php

namespace ChewieLab\Nissan;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use ChewieLab\Nissan;

class EngineTemp implements Tickable
{
    use Ticks;

    public Animatable $value;

    public $revCount = 0;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(12)->pauseAfter(20);
    }

    public function onTick(): void
    {
        $this->value->animate();

        if (!$this->value->isAnimating()) {
            $this->value->to($this->prompt->carStarted ? 3 : 0);
        }
    }

    public function startCar()
    {
        $this->value->to(3);
    }

    public function stopCar()
    {
        $this->value->to(0);
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 2 === 0) {
            $this->value->toRelative(1);
        }
    }
}
