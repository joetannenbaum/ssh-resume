<?php

namespace ChewieLab\Prong;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use ChewieLab\Prong;

class Title implements Tickable
{
    use Ticks;

    public Animatable $value;

    public function __construct(protected Prong $prompt)
    {
        $this->value = Animatable::fromValue(9)->lowerLimit(0);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function hide()
    {
        $this->value->to(0);
    }
}
