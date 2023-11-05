<?php

namespace ChewieLab\Game;

use Illuminate\Support\Collection;

class Background
{
    public $elements = [];

    protected int $tickCounter = 0;

    protected int $nextElAt = 0;

    protected int $lowerBound = 50;

    protected int $upperBound = 100;

    protected Collection $chars;

    protected Collection $colors;

    public function __construct()
    {
        $this->chars = collect(['█', '█' . PHP_EOL . '█', '█' . PHP_EOL . '█' . PHP_EOL . '█']);
        $this->colors = collect(['red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white']);
    }

    public function tick()
    {
        if ($this->tickCounter === $this->nextElAt) {
            $this->elements[] = [
                'x'     => 100,
                'y'     => 100,
                'char'  => $this->chars->random(),
                'color' => $this->colors->random(),
            ];

            $this->nextElAt = $this->tickCounter + rand($this->lowerBound, $this->upperBound);
        }

        if ($this->tickCounter % 5 === 0) {
            foreach ($this->elements as $key => $element) {
                $this->elements[$key]['x'] -= 1;
            }
        }

        $this->elements = array_filter($this->elements, function ($element) {
            return $element['x'] > 0;
        });

        $this->tickCounter++;
    }
}
