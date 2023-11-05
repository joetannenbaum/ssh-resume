<?php

namespace ChewieLab;

use Chewie\Themes\Default\GraphRenderer;
use Laravel\Prompts\Prompt;

class Graph extends Prompt
{
    public array $numbers;

    public array $nextNumbers;

    public array $colors = [];

    public function __construct()
    {
        static::$themes['default'][Graph::class] = GraphRenderer::class;

        // tput smcup
        static::output()->write("\e[?1049h");
    }

    public function __destruct()
    {
        // tput rmcup
        static::output()->write("\e[?1049l");
    }

    public function graph()
    {
        $this->hideCursor();

        while (true) {
            $this->generateNextNumbers();
            $this->incrementNumbers();

            sleep(1);
        }
    }

    public function value(): mixed
    {
    }

    protected function incrementNumbers()
    {
        $stillIncrementing = false;

        foreach ($this->nextNumbers as $index => $number) {
            if ($this->numbers[$index] < $number) {
                $this->numbers[$index]++;
                $stillIncrementing = true;
            } elseif ($this->numbers[$index] > $number) {
                $this->numbers[$index]--;
                $stillIncrementing = true;
            }
        }

        $this->render();

        if ($stillIncrementing) {
            usleep(50_000);
            $this->incrementNumbers();
        }
    }

    protected function generateNextNumbers(): void
    {
        $count = 17;

        if (!isset($this->numbers)) {
            $this->numbers = collect(range(0, $count))->map(fn () => 0)->toArray();
        }

        $this->nextNumbers = collect(range(0, $count))->map(fn () => rand(1, 15))->toArray();

        $colors = ['red', 'green', 'yellow', 'blue', 'magenta', 'cyan', 'white'];

        $this->colors = collect(range(0, $count))->map(fn () => $colors[array_rand($colors)])->toArray();
    }
}
