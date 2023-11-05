<?php

namespace ChewieLab\Game;

use Chewie\Concerns\Animates;

class Player
{
    use Animates;

    public $elements = [];

    protected int $tickCounter = 0;

    protected int $waitFor = 0;

    public function __construct()
    {
        $this->registerValue('height', 0);
    }

    public function tick()
    {
        $this->moveTowards('height');

        if ($this->equal('height')) {
            if ($this->waitFor > 0) {
                $this->waitFor--;
            } else {
                $this->setNext('height', 0);
            }
        }

        $this->tickCounter++;
    }

    public function jump()
    {
        $this->incrementNext('height');
        $this->waitFor = 10;
    }
}
