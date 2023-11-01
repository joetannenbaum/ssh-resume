<?php

namespace Chewie\Nissan;

use Chewie\Dashboard\DashboardComponent;

class Rpm implements DashboardComponent
{
    public $value = 0;

    public $nextValue = 0;

    public $tickCount = 0;

    protected $lowerBound = 1;

    protected $upperBound = 20;

    public $factor = 2;

    public $multiplier;

    public $nextMultiplier;

    public $cycles = 0;

    public $carStarted = false;

    public function __construct()
    {
        $this->multiplier = $this->factor;
        $this->nextMultiplier = $this->factor;
    }

    public function tick(): void
    {
        if ($this->value !== $this->nextValue) {
            $this->value += $this->value < $this->nextValue ? 1 : -1;
        }

        if ($this->multiplier !== $this->nextMultiplier) {
            $this->multiplier += $this->multiplier < $this->nextMultiplier ? $this->factor : -$this->factor;
        } else {
            if ($this->cycles > 0) {
                $this->cycles--;
            } else {
                // Cool the engine down
                $this->nextMultiplier = max($this->carStarted ? $this->factor * 2 : $this->factor, $this->multiplier - $this->factor);
                $this->nextValue = $this->carStarted ?  3 : 0;
            }
        }


        $this->tickCount++;
    }

    public function startCar()
    {
        $this->nextMultiplier = $this->factor * 2;
        $this->carStarted = true;
        $this->nextValue = 3;
    }

    public function stopCar()
    {
        $this->nextMultiplier = $this->factor;
        $this->carStarted = false;
        $this->nextValue = 0;
    }

    public function rev()
    {
        $this->nextMultiplier = min(100, $this->nextMultiplier + $this->factor);
        $this->cycles = 10;
        $this->nextValue = min(38, $this->value + rand(3, 5));
    }

    public function brake()
    {
        $this->nextMultiplier = max($this->factor, $this->nextMultiplier - $this->factor);
        $this->cycles = 10;
        $this->nextValue = min(38, $this->value - rand(3, 5));
    }
}
