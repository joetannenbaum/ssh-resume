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
        'Links',
        'Experience',
        'Skills',
        'Education',
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
        ray('run');
        $this->setup($this->showResume(...));
    }

    public function value(): mixed
    {
        //
    }

    protected function showResume()
    {
        ray('showResume');
        $this->loop($this->runLoop(...));
    }

    protected function runLoop()
    {
        $this->render();

        $key = KeyPressListener::once();

        match ($key) {
            'q' => $this->terminal()->exit(),
            Key::TAB => $this->focused = $this->focused === 'content' ? 'navigation' : 'content',
            default => null,
        };

        match ($key) {
            Key::UP, Key::UP_ARROW => $this->scrollPosition = max(0, $this->scrollPosition - 2),
            Key::DOWN, Key::DOWN_ARROW => $this->scrollPosition += 2,
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

        if ($key === Key::CTRL_C) {
            $this->terminal()->exit();
        }
    }
}
