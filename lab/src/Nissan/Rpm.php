<?php

namespace ChewieLab\Nissan;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use ChewieLab\Nissan;

class Rpm implements Tickable
{
    use Ticks;

    public Animatable $value;

    public Animatable $rpms;

    public Animatable $multiplier;

    public $factor = 2;

    protected $rpmsMultiplier = 3;

    public function __construct(protected Nissan $prompt)
    {
        $this->value = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(99);
        $this->rpms = Animatable::fromValue(0)->lowerLimit(0)->upperLimit(99);
        $this->multiplier = Animatable::fromValue($this->factor)->lowerLimit($this->factor)->upperLimit(38)->step($this->factor)->pauseAfter(10);
    }

    public function onTick(): void
    {
        $this->value->animate();
        $this->rpms->animate();
        $this->multiplier->animate();

        if (!$this->multiplier->isAnimating()) {
            // Cool the engine down
            if ($this->prompt->carStarted) {
                $this->multiplier->to($this->factor * 2);
                $this->value->to(3);
            } else {
                $this->multiplier->to($this->factor);
                $this->value->to(0);
            }

            $this->rpms->to(0);
        }
    }

    public function startCar()
    {
        $this->multiplier->to($this->factor * 2);
        $this->value->to(3);
    }

    public function stopCar()
    {
        $this->multiplier->to($this->factor);
        $this->value->to(0);
    }

    public function rev()
    {
        $this->multiplier->toRelative($this->factor);
        $this->value->toRelative(rand(3, 5));
        $this->rpms->toRelative($this->value->next() * $this->rpmsMultiplier);
    }

    public function brake()
    {
        $this->multiplier->toRelative(-$this->factor);
        $this->value->toRelative(-rand(3, 5));
        $this->rpms->toRelative($this->value->next() * $this->rpmsMultiplier);
    }
}
