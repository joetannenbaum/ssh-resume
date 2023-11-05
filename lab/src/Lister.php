<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Concerns\SetsUpAndResets;
use Chewie\Themes\Default\ListerRenderer;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class Lister extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use SetsUpAndResets;
    use TypedValue;

    public Collection $items;

    public Collection $versions;

    public Collection $log;

    public string $total = '';

    public bool $isFirstRender = true;

    public bool $showActivityLog = false;

    public function __construct(public string $path)
    {
        $this->registerTheme(ListerRenderer::class);

        $this->items = collect();
        $this->versions = collect();
        $this->log = collect();

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function watch()
    {
        $this->setup($this->listAndRender(...));
    }

    public function value(): mixed
    {
    }

    protected function listAndRender()
    {
        while (true) {
            $output = shell_exec('ls -lAh ' . $this->path);

            $items = collect(explode(PHP_EOL, $output));

            $this->total = str_replace('total ', '', $items->shift());

            if (!$this->isFirstRender) {
                $this->versions->push($this->items);
                $this->versions = $this->versions->take(-20);
            }

            $this->items = $items->map(
                fn ($item) => preg_split('/\s+/', $item),
            )->filter(fn ($parts) => count($parts) > 1)->map(
                function ($parts) {
                    $permissions = array_shift($parts);
                    $hardLinks = array_shift($parts);
                    $owner = array_shift($parts);
                    $group = array_shift($parts);
                    $size = array_shift($parts);
                    $month = array_shift($parts);
                    $day = array_shift($parts);
                    $time = array_shift($parts);
                    $name = implode(' ', $parts);

                    return [
                        'permissions' => $permissions,
                        'hardLinks'   => $hardLinks,
                        'owner'       => $owner,
                        'group'       => $group,
                        'size'        => $size,
                        'month'       => $month,
                        'day'         => $day,
                        'time'        => $time,
                        'name'        => $name,
                        'date'        => "{$month} {$day} {$time}",
                        'is_dir'      => substr($permissions, 0, 1) === 'd',
                    ];
                },
            );

            if (!$this->isFirstRender) {
                $this->versions->last()->filter(
                    fn ($lastVersion) => $this->items->firstWhere('name', $lastVersion['name']) === null,
                )->each(fn ($item) => $this->log->push([
                    'type'      => 'deleted',
                    'item'      => $item,
                    'timestamp' => time(),
                ]));

                $this->items->filter(
                    fn ($item) => $this->versions->last()->firstWhere('name', $item['name']) === null,
                )->each(fn ($item) => $this->log->push([
                    'type'      => 'created',
                    'item'      => $item,
                    'timestamp' => time(),
                ]));

                $lastVersion = $this->versions->last();

                $this->items->filter(
                    fn ($item) => $this->versions->last()->firstWhere('name', $item['name']) !== null,
                )->each(function ($item) use ($lastVersion) {
                    $lastVersionOfItem = $lastVersion->firstWhere('name', $item['name']);

                    if ($lastVersionOfItem['permissions'] !== $item['permissions']) {
                        $this->log->push([
                            'type'      => 'modified',
                            'from'      => $lastVersionOfItem['permissions'],
                            'to'        => $item['permissions'],
                            'item'      => $item,
                            'timestamp' => time(),
                        ]);
                    }

                    if ($lastVersionOfItem['owner'] !== $item['owner']) {
                        $this->log->push([
                            'type'      => 'modified',
                            'from'      => $lastVersionOfItem['owner'],
                            'to'        => $item['owner'],
                            'item'      => $item,
                            'timestamp' => time(),
                        ]);
                    }

                    if ($lastVersionOfItem['group'] !== $item['group']) {
                        $this->log->push([
                            'type'      => 'modified',
                            'from'      => $lastVersionOfItem['group'],
                            'to'        => $item['group'],
                            'item'      => $item,
                            'timestamp' => time(),
                        ]);
                    }

                    if ($lastVersionOfItem['size'] !== $item['size']) {
                        $this->log->push([
                            'type'      => 'modified',
                            'from'      => $lastVersionOfItem['size'],
                            'to'        => $item['size'],
                            'item'      => $item,
                            'timestamp' => time(),
                        ]);
                    }

                    if ($lastVersionOfItem['date'] !== $item['date']) {
                        $this->log->push([
                            'type'      => 'modified',
                            'from'      => $lastVersionOfItem['date'],
                            'to'        => $item['date'],
                            'item'      => $item,
                            'timestamp' => time(),
                        ]);
                    }
                });
            }

            $this->render();

            $key = KeyPressListener::once();

            if ($key === Key::CTRL_C) {
                $this->showCursor();
                $this->terminal()->exit();
                break;
            }

            if ($key === 'a') {
                $this->showActivityLog = !$this->showActivityLog;
            }

            $this->isFirstRender = false;

            usleep(100_000);
        }
    }
}
