<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\KeyPressListener;
use Chewie\RegistersThemes;
use ChewieLab\Nissan\Battery;
use ChewieLab\Nissan\EngineTemp;
use ChewieLab\Nissan\Fuel;
use ChewieLab\Nissan\OilLevel;
use ChewieLab\Nissan\Rpm;
use ChewieLab\Themes\Default\NissanRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Nissan extends Prompt
{
    use CreatesAnAltScreen;
    use Loops;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public int $sleepFor = 50_000;

    public bool $carStarted = false;

    public function __construct()
    {
        $this->registerTheme(NissanRenderer::class);

        $this->registerLoopable(Fuel::class);
        $this->registerLoopable(EngineTemp::class);
        $this->registerLoopable(OilLevel::class);
        $this->registerLoopable(Battery::class);
        $this->registerLoopable(Rpm::class);

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->showDashboard(...));
    }

    public function value(): mixed
    {
    }

    protected function showDashboard()
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

        if ($key === Key::ENTER) {
            $this->carStarted = !$this->carStarted;

            foreach ($this->loopables as $component) {
                if ($this->carStarted) {
                    $component->startCar();
                } else {
                    $component->stopCar();
                }
            }
        }

        if (!$this->carStarted) {
            return;
        }

        if ($key === ' ') {
            foreach ($this->loopables as $component) {
                $component->rev();
            }
        }

        if ($key === 'b') {
            $this->loopables[Rpm::class]->brake();
        }
    }
}
