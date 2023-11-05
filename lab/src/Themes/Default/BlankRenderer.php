<?php

namespace ChewieLab\Themes\Default;

use Chewie\Blank;
use Laravel\Prompts\Themes\Default\Renderer;

class BlankRenderer extends Renderer
{
    public function __invoke(Blank $prompt): string
    {
        return $this;
    }
}
