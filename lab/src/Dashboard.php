<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\KeyPressListener;
use Chewie\RegistersThemes;
use ChewieLab\Dashboard\BarGraph;
use ChewieLab\Dashboard\Chat;
use ChewieLab\Dashboard\HalPulse;
use ChewieLab\Dashboard\Health;
use ChewieLab\Dashboard\PercentageBar;
use ChewieLab\Themes\Default\DashboardRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Dashboard extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public array $components = [];

    public function __construct()
    {
        $this->registerTheme(DashboardRenderer::class);

        $this->registerLoopable(Health::class);
        $this->registerLoopable(PercentageBar::class);
        $this->registerLoopable(HalPulse::class);
        $this->registerLoopable(Chat::class);
        $this->registerLoopable(BarGraph::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup(fn () => $this->loop($this->showDashboard(...), 100_000));
    }

    public function value(): mixed
    {
        //
    }

    public function valueWithCursor(int $maxWidth): string
    {
        $chat = $this->loopable(Chat::class);

        if ($chat->currentlyTyping === '') {
            return $this->dim($this->addCursor('Chat with HAL', 0, $maxWidth));
        }

        return $this->addCursor($chat->currentlyTyping, strlen($chat->currentlyTyping), $maxWidth);
    }

    protected function showDashboard(): void
    {
        $this->render();

        match (KeyPressListener::once()) {
            Key::CTRL_C => $this->terminal()->exit(),
            Key::UP_ARROW, Key::UP => $this->sleepBetweenLoops = max(50_000, $this->sleepBetweenLoops - 50_000),
            Key::DOWN_ARROW, Key::DOWN => $this->sleepBetweenLoops += 50_000,
            default => null,
        };
    }
}
