<?php

namespace Chewie\Concerns;

trait Loops
{
    public array $components = [];

    protected function registerComponent($component): void
    {
        $this->components[$component] = new $component($this);
    }

    public function component($component)
    {
        return $this->components[$component];
    }

    protected function loop($cb, int $sleepFor = 50_000)
    {
        while (true) {
            $cb();

            foreach ($this->components as $component) {
                $component->tick();
            }

            usleep($sleepFor);
        }
    }
}
