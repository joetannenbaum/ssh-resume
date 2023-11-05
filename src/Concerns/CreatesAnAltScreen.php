<?php

namespace Chewie\Concerns;

trait CreatesAnAltScreen
{
    public function createAltScreen()
    {
        // tput smcup
        static::output()->write("\e[?1049h");
    }

    public function exitAltScreen()
    {
        // tput rmcup
        static::output()->write("\e[?1049l");
    }
}
