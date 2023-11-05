<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\RegistersThemes;
use ChewieLab\Themes\Default\PlaygroundRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Prompt;

class Playground extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;

    public function __construct()
    {
        $this->registerTheme(PlaygroundRenderer::class);

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        // $this->exitAltScreen();
    }

    public function run()
    {
        $this->render();
    }

    public function value(): mixed
    {
    }
}
