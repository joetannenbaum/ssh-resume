<?php

namespace Chewie;

use Chewie\Concerns\CreatesAnAltScreen;
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

    public array $components = [];

    public int $sleepFor = 50_000;

    public bool $carStarted = false;

    public function __construct()
    {
        $this->registerTheme(NissanRenderer::class);

        collect([
            Fuel::class,
            EngineTemp::class,
            OilLevel::class,
            Battery::class,
            Rpm::class,
        ])->each(fn ($component) => $this->components[$component] = new $component());

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        // $this->exitAltScreen();
    }

    public function run()
    {
        $this->setup($this->showDashboard(...));
    }

    protected function showDashboard()
    {
        while (true) {
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

            if ($this->carStarted) {
                if ($key === ' ') {
                    foreach ($this->components as $component) {
                        $component->rev();
                    }
                }

                if ($key === 'b') {
                    $this->components[Rpm::class]->brake();
                }
            }

            foreach ($this->components as $component) {
                if (is_array($component)) {
                    foreach ($component as $c) {
                        $c->tick();
                    }

                    continue;
                }

                $component->tick();
            }

            usleep($this->sleepFor);
        }
    }

    public function value(): mixed
    {
    }
}
