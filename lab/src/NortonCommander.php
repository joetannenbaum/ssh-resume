<?php

namespace ChewieLab;

use Chewie\Themes\Default\NortonCommanderRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class NortonCommander extends Prompt
{
    use TypedValue;

    public array $panels = [];

    public function __construct()
    {
        $this->panels = [
            new CommanderPanel,
            new CommanderPanel(getcwd() . '/vendor'),
        ];

        $this->panels[0]->active = true;

        static::$themes['default'][NortonCommander::class] = NortonCommanderRenderer::class;

        // tput smcup
        static::output()->write("\e[?1049h");

        $this->listenForHotkeys();
    }

    /**
     * Get the value of the prompt.
     */
    public function value(): bool
    {
        return true;
    }

    public function __destruct()
    {
        // tput rmcup
        static::output()->write("\e[?1049l");
    }

    protected function listenForHotkeys(): void
    {
        $this->on('key', function ($key) {
            if ($key[0] === "\e") {
                match ($key) {
                    Key::UP, Key::UP_ARROW => $this->handleUpKey(),
                    Key::DOWN, Key::DOWN_ARROW => $this->handleDownKey(),
                    Key::SHIFT_TAB => $this->handleTab(),
                    default        => null,
                };

                return;
            }

            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($key === Key::ENTER) {
                    $this->handleEnterKey();

                    return;
                }

                if ($key === Key::TAB) {
                    $this->handleTab();

                    return;
                }
            }
        });
    }

    protected function handleTab()
    {
        foreach ($this->panels as $panel) {
            $panel->active = !$panel->active;
        }
    }

    protected function handleEnterKey()
    {
        foreach ($this->panels as $panel) {
            if ($panel->active) {
                $file = $panel->files[$panel->selectedIndex];

                if ($file['type'] === 'dir') {
                    $panel->setDirectory($file['name']);
                }
            }
        }
    }

    protected function handleUpKey()
    {
        foreach ($this->panels as $panel) {
            if ($panel->active) {
                $panel->selectedIndex = max(0, $panel->selectedIndex - 1);
            }
        }
    }

    protected function handleDownKey()
    {
        foreach ($this->panels as $panel) {
            if ($panel->active) {
                $panel->selectedIndex = min(count($panel->files) - 1, $panel->selectedIndex + 1);
            }
        }
    }

    protected function quit(): void
    {
        $this->state = 'cancel';
        exit;
    }
}
