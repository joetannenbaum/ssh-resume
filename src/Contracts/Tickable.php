<?php

namespace Chewie\Contracts;

use Laravel\Prompts\Prompt;

interface Tickable
{
    public static function make(Prompt $prompt): static;

    public function tick(): void;
}
