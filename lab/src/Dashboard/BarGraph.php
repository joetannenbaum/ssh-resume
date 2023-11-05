<?php

namespace ChewieLab\Dashboard;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;

class BarGraph implements Tickable
{
    use Ticks;

    public $values = [];

    protected $lowerBound = 25;

    protected $upperBound = 75;

    public function __construct()
    {
        $this->values = [
            Animatable::fromValue(rand($this->lowerBound, $this->upperBound)),
            Animatable::fromValue(rand($this->lowerBound, $this->upperBound)),
            Animatable::fromValue(rand($this->lowerBound, $this->upperBound)),
        ];

        // $this->nextValues = $this->values = [
        //     rand($this->lowerBound, $this->upperBound),
        //     rand($this->lowerBound, $this->upperBound),
        //     rand($this->lowerBound, $this->upperBound),
        // ];
    }

    public function onTick(): void
    {
        // if ($this->tickCount % 1 !== 0) {
        //     $this->tickCount++;

        //     return;
        // }

        foreach ($this->values as $index => $value) {
            if ($value->isAnimating()) {
                $value->animate();
            } else {
                $value->to(rand($this->lowerBound, $this->upperBound));
            }
            // if ($value === $this->nextValues[$index]) {
            //     $this->nextValues[$index] = rand($this->lowerBound, $this->upperBound);
            // } else {
            //     $this->values[$index] += $value < $this->nextValues[$index] ? 1 : -1;
            // }
        }

        // $this->tickCount++;
    }
}
