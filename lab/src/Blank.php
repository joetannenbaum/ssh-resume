<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Themes\Default\BlankRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class Blank extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;

    public function __construct()
    {
        $this->registerTheme(BlankRenderer::class);

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        // $this->exitAltScreen();
    }

    public function value(): mixed
    {
    }
}
