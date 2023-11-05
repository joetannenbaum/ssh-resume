<?php

namespace Chewie\Concerns;

trait Loops
{
    public array $loopables = [];

    protected int $sleepBetweenLoops = 50_000;

    public function loopable(string $component)
    {
        return $this->loopables[$component];
    }

    protected function registerLoopable(string $component, string $key = null): void
    {
        $this->loopables[$key ?? $component] = new $component($this);
    }

    protected function clearRegisteredLoopables(): void
    {
        $this->loopables = [];
    }

    protected function loop($cb, int $sleepFor = 50_000)
    {
        $this->sleepBetweenLoops = $sleepFor;

        while (true) {
            $continue = $cb();

            if ($continue === false) {
                break;
            }

            foreach ($this->loopables as $component) {
                $component->tick();
            }

            usleep($this->sleepBetweenLoops);
        }
    }
}
