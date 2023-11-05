<?php

namespace ChewieLab\Themes\Default;

use Chewie\Output\Bar;
use Chewie\Output\Lines;
use ChewieLab\Playground;
use Laravel\Prompts\Themes\Default\Renderer;

class PlaygroundRenderer extends Renderer
{
    public function __invoke(Playground $prompt): string
    {
        $bars = collect();

        foreach (range(1, 40) as $i) {
            $bars->push(Bar::vertical($i)->asCols());
        }

        Lines::fromColumns($bars)->spacing(1)->alignBottom()->lines()->each(fn ($line) => $this->line($line));

        return $this;
    }
}
