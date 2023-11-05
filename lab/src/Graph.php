<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\KeyPressListener;
use Chewie\RegistersThemes;
use ChewieLab\Themes\Default\GraphRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Graph extends Prompt
{
    use RegistersThemes;
    use CreatesAnAltScreen;

    public array $numbers;

    public array $nextNumbers;

    public array $colors = [];

    public function __construct()
    {
        $this->registerTheme(GraphRenderer::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function graph()
    {
        $this->hideCursor();

        while (true) {
            $this->generateNextNumbers();
            $this->incrementNumbers();

            if (KeyPressListener::once() === Key::CTRL_C) {
                $this->terminal()->exit();
            }

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
