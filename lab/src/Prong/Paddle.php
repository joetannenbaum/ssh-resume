<?php

namespace ChewieLab\Prong;

use Chewie\Concerns\Ticks;
use Chewie\Contracts\Tickable;
use Chewie\Support\Animatable;
use ChewieLab\Prong;

class Paddle implements Tickable
{
    use Ticks;

    public Animatable $value;

    public function __construct(protected Prong $prompt)
    {
        $this->value = Animatable::fromValue((int) floor($prompt->height / 2))->lowerLimit(0)->upperLimit($this->prompt->height - 5);
    }

    public function onTick(): void
    {
        $this->value->animate();
    }

    public function moveUp()
    {
        $this->value->toRelative(-1);
    }

    public function moveDown()
    {
        $this->value->toRelative(1);
    }
}
