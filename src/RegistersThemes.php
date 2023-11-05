<?php

namespace Chewie;

trait RegistersThemes
{
    public function registerTheme(string $theme): void
    {
        static::$themes['default'][static::class] = $theme;
    }
}
