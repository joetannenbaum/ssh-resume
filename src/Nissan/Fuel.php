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

    public function tick(): void
    {
        if ($this->value !== $this->nextValue) {
            $this->value += $this->value < $this->nextValue ? 1 : -1;
        }

        $this->tickCount++;
    }
}
