<?php

namespace Chewie;

use Chewie\Themes\Default\FieldsetRenderer;
use Laravel\Prompts\Prompt;

class Fieldset extends Prompt
{
    // use TypedValue;

    public array $prompts = [];

    public array $values = [];

    public int $activePrompt = 0;

    public function __construct(...$prompts)
    {
        $this->prompts = $prompts;

        static::$themes['default'][Fieldset::class] = FieldsetRenderer::class;

        // tput smcup
        // static::output()->write("\e[?1049h");
    }

    public function prompt(): mixed
    {
        foreach ($this->prompts as $prompt) {
            $prompt->render();
        }

        $this->moveCursor(0, count($this->prompts) * -3);

        foreach ($this->prompts as $index => $prompt) {
            $this->values[$index] = $prompt->prompt();
        }

        return true;
    }

    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }

    // public function __destruct()
    // {
    //     // tput rmcup
    //     static::output()->write("\e[?1049l");
    // }
}
