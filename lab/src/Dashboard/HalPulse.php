<?php

namespace ChewieLab\Dashboard;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;

class HalPulse implements Tickable
{
    use Ticks;

    public $frames = ['●', '○'];

    public $current = 0;

    public $dimmed = false;

    public function onTick(): void
    {
        if ($this->isNthTick(10)) {
            $this->current = ($this->current + 1) % count($this->frames);
            $this->dimmed = !$this->dimmed;
        }
    }
}
