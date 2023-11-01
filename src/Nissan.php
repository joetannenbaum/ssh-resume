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
                    $this->components[Fuel::class]->nextValue = 15;
                    $this->components[EngineTemp::class]->nextValue = 3;
                    $this->components[OilLevel::class]->nextValue = 10;
                    $this->components[Battery::class]->nextValue = 12;
                    $this->components[Rpm::class]->startCar();
                } else {
                    $this->components[Fuel::class]->nextValue = 0;
                    $this->components[EngineTemp::class]->nextValue = 0;
                    $this->components[OilLevel::class]->nextValue = 0;
                    $this->components[Battery::class]->nextValue = 0;
                    $this->components[Rpm::class]->stopCar();
                }
            }

            if ($this->carStarted) {
                if ($key === ' ') {
                    $this->components[Rpm::class]->rev();
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
