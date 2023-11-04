<?php

namespace Chewie;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\Loops;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Nissan\Battery;
use Chewie\Nissan\EngineTemp;
use Chewie\Nissan\Fuel;
use Chewie\Nissan\OilLevel;
use Chewie\Nissan\Rpm;
use Chewie\Themes\Default\NissanRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Nissan extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;
    use SetsUpAndResets;
    use Loops;

    public int $sleepFor = 50_000;

    public bool $carStarted = false;

    public function __construct()
    {
        $this->registerTheme(NissanRenderer::class);

        $this->registerComponent(Fuel::class);
        $this->registerComponent(EngineTemp::class);
        $this->registerComponent(OilLevel::class);
        $this->registerComponent(Battery::class);
        $this->registerComponent(Rpm::class);

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->showDashboard(...));
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

            if ($this->carStarted) {
                foreach ($this->components as $component) {
                    $component->startCar();
                }
            } else {
                foreach ($this->components as $component) {
                    $component->stopCar();
                }
            }
        }

        if (!$this->carStarted) {
            return;
        }

        if ($key === ' ') {
            foreach ($this->components as $component) {
                $component->rev();
            }
        }

        if ($key === 'b') {
            $this->components[Rpm::class]->brake();
        }
    }

    public function value(): mixed
    {
    }
}
