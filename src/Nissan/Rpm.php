<?php

namespace Chewie\Nissan;

use Chewie\Dashboard\DashboardComponent;

class Rpm implements DashboardComponent
{
    public $value = 0;

    public $nextValue = 0;

    public $tickCount = 0;

    public $speed = 0;

    public $nextSpeed = 0;

    protected $speedMultiplier = 3;

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

        if ($this->speed !== $this->nextSpeed) {
            $speedDiff = abs($this->speed - $this->nextSpeed);

            if ($speedDiff < 3) {
                $this->speed = $this->nextSpeed;
            } else {
                $this->speed += $this->speed < $this->nextSpeed ? 3 : -3;
            }
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
                $this->nextSpeed = 0;
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
        $this->nextMultiplier = min(38, $this->nextMultiplier + $this->factor);
        $this->nextValue = min(38, $this->value + rand(3, 5));
        $this->nextSpeed = min(99, $this->nextValue * $this->speedMultiplier);
        $this->cycles = 10;
    }

    public function brake()
    {
        $this->nextMultiplier = max($this->factor, $this->nextMultiplier - $this->factor);
        $this->nextValue = max($this->factor, $this->value - rand(3, 5));
        $this->nextSpeed = max(0, $this->nextValue * $this->speedMultiplier);
        $this->cycles = 10;
    }
}
