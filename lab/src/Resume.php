<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\KeyPressListener;
use Chewie\RegistersThemes;
use ChewieLab\Themes\Default\ResumeRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Resume extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public int $page = 0;

    public int $selectedPage = 0;

    public int $scrollPosition = 0;

    public int $sidebarWidth = 0;

    public int $maxTextWidth = 0;

    public int $height = 0;

    public int $width = 0;

    public string $focused = 'content';

    public array $navigation = [
        'Summary',
        'Experience',
        'Skills',
        'Education',
        'References',
    ];

    public function __construct()
    {
        $this->registerTheme(ResumeRenderer::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->showResume(...));
    }

    public function value(): mixed
    {
        //
    }

    protected function showResume()
    {
        $this->loop($this->runLoop(...));
    }

    protected function runLoop()
    {
        $this->render();

        $key = KeyPressListener::once();

        match ($key) {
            'e' => exec('open mailto:joe@joe.codes'),
            's' => exec('open https://joe.codes'),
            'b' => exec('open https://blog.joe.codes'),
            'g' => exec('open https://github.com/joetannenbaum'),
            'l' => exec('open https://www.linkedin.com/in/joe-tannenbaum-27724221'),
            't' => exec('open https://twitter.com/joetannenbaum'),
            Key::TAB => $this->focused = $this->focused === 'content' ? 'navigation' : 'content',
            default => null,
        };

        if ($this->focused === 'navigation') {
            match ($key) {
                Key::UP, Key::UP_ARROW, Key::LEFT, Key::LEFT_ARROW => $this->selectedPage = max(0, $this->selectedPage - 1),
                Key::DOWN, Key::DOWN_ARROW, Key::RIGHT, Key::RIGHT_ARROW => $this->selectedPage = min(count($this->navigation) - 1, $this->selectedPage + 1),
                default => null,
            };

            if ($key === Key::ENTER) {
                $this->page = $this->selectedPage;
                $this->focused = 'content';
            }
        }

        if ($this->focused === 'content') {
            match ($key) {
                Key::UP, Key::UP_ARROW => $this->scrollPosition = max(0, $this->scrollPosition - 1),
                Key::DOWN, Key::DOWN_ARROW => $this->scrollPosition += 1,
                default => null,
            };

            if (in_array($key, [Key::RIGHT, Key::RIGHT_ARROW])) {
                $this->page = $this->selectedPage = min(count($this->navigation) - 1, $this->page + 1);
                $this->scrollPosition = 0;
            }

            if (in_array($key, [Key::LEFT, Key::LEFT_ARROW])) {
                $this->page = $this->selectedPage = max(0, $this->page - 1);
                $this->scrollPosition = 0;
            }
        }

        if ($key === Key::CTRL_C) {
            $this->terminal()->exit();
        }
    }
}
