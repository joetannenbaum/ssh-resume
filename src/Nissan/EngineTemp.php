<?php

namespace Chewie\Nissan;

use Chewie\Dashboard\DashboardComponent;

class EngineTemp implements DashboardComponent
{
    public $value = 0;

    public $nextValue = 0;

    public $tickCount = 0;

    protected $lowerBound = 1;

    protected $upperBound = 12;

    public $revCount = 0;

    protected $cycles = 0;

    public $carStarted = false;

    public function tick(): void
    {
        if ($this->value !== $this->nextValue) {
            $this->value += $this->value < $this->nextValue ? 1 : -1;
        } else {
            if ($this->cycles > 0) {
                $this->cycles--;
            } else {
                // Cool the engine down
                $this->nextValue = $this->carStarted ?  3 : 0;
            }
        }
        $this->tickCount++;
    }

    public function startCar()
    {
        $this->carStarted = true;
        $this->nextValue = 3;
    }

    public function stopCar()
    {
        $this->carStarted = false;
        $this->nextValue = 0;
    }

    public function rev()
    {
        $this->revCount++;

        if ($this->revCount % 2 === 0) {
            $this->nextValue = min($this->upperBound, $this->value + 1);
            $this->cycles = 20;
        }
    }
}
