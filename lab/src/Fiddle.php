<?php

namespace ChewieLab;

use Chewie\Themes\Default\FiddleRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Fiddle extends Prompt
{
    public int $page = 0;

    public array $pageContent = [
        'This is the home screen',
        'This is the about screen',
        'This is the contact screen',
    ];

    public array $nav = [
        'Home',
        'About',
        'Contact',
    ];

    public function __construct()
    {
        static::$themes['default'][Fiddle::class] = FiddleRenderer::class;

        // tput smcup
        static::output()->write("\e[?1049h");

        $this->listenForNav();
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

    protected function listenForNav()
    {
        $this->clearListeners();

        $this->on('key', function ($key) {
            if ($key[0] === "\e") {
                match ($key) {
                    Key::UP, Key::UP_ARROW => $this->page = max(0, $this->page - 1),
                    Key::DOWN, KEY::DOWN_ARROW => $this->page = min(count($this->nav) - 1, $this->page + 1),
                    default => null,
                };

                return;
            }

            if ($key === Key::ENTER) {
                $this->state = 'detail';
                $this->listenForDetail();

                return;
            }

            if ($key === 'q') {
                $this->quit();

                return;
            }
        });
    }

    protected function listenForDetail()
    {
        $this->clearListeners();

        $this->on('key', function ($key) {
            if ($key[0] === "\e") {
                if ($key === Key::LEFT || $key === Key::LEFT_ARROW) {
                    $this->state = 'nav';
                    $this->listenForNav();

                    return;
                }

                return;
            }

            if ($key === 'q') {
                $this->quit();

                return;
            }
        });
    }

    protected function quit()
    {
        $this->clearListeners();

        exit(1);
    }
}
