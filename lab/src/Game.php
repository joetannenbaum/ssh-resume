<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Game\Background;
use Chewie\Game\Player;
use Chewie\Themes\Default\GameRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Game extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public function __construct()
    {
        $this->registerTheme(GameRenderer::class);

        $this->registerLoopable(Background::class);
        $this->registerLoopable(Player::class);

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        // $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->start(...));
    }

    public function value(): mixed
    {
    }

    protected function start()
    {
        $this->loop($this->runLoop(...));
    }

    protected function runLoop()
    {
        $this->render();

        $key = KeyPressListener::once();

        if ($key === Key::CTRL_C) {
            $this->terminal()->exit();
        }

        if ($key === ' ') {
            $this->component(Player::class)->jump();
        }
    }
}
