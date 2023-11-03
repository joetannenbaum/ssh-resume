<?php

namespace Chewie\Nissan;

use Chewie\Dashboard\DashboardComponent;

class Fuel implements DashboardComponent
{
    public $value = 0;

    public $nextValue = 0;

    public $tickCount = 0;

    protected $lowerBound = 1;

    protected $upperBound = 20;

    public $carStarted = false;

    public $revCount = 0;

    public function tick(): void
    {
        if ($this->value !== $this->nextValue) {
            $this->value += $this->value < $this->nextValue ? 1 : -1;
        }

        $this->tickCount++;
    }

    public function startCar()
    {
        $this->carStarted = true;
        $this->nextValue = 15;
    }

    public function stopCar()
    {
        $this->carStarted = false;
        $this->nextValue = 0;
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 7 === 0) {
            $this->nextValue = max(0, $this->value - 1);
        }
    }
}
