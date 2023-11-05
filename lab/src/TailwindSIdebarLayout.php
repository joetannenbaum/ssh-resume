<?php

namespace ChewieLab;

use Chewie\Themes\Default\TailwindSidebarLayoutRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class TailwindSidebarLayout extends Prompt
{
    public int $sidebarIndex = 0;

    public array $sidebar = [
        'dashboard',
        'team',
        'projects',
        'calendar',
        'documents',
        'reports',
    ];

    public function __construct()
    {
        static::$themes['default'][TailwindSidebarLayout::class] = TailwindSidebarLayoutRenderer::class;

        $this->listenForSidebarKeys();
    }

    public function value(): mixed
    {
    }

    protected function listenForSidebarKeys(): void
    {
        $this->on('key', function ($key) {
            if (str_starts_with($key, "\e")) {
                match ($key) {
                    Key::UP, Key::UP_ARROW => $this->sidebarIndex = max(0, $this->sidebarIndex - 1),
                    Key::DOWN, Key::DOWN_ARROW => $this->sidebarIndex = min(count($this->sidebar) - 1, $this->sidebarIndex + 1),
                };

                return;
            }

            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($key === Key::ENTER) {
                    $this->state = $this->sidebar[$this->sidebarIndex];
                }
            }
        });
    }
}
