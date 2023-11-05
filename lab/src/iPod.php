<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\RegistersThemes;
use Chewie\Themes\Default\iPodRenderer;
use Illuminate\Support\Collection;
use Laravel\Prompts\Prompt;

class iPod extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;

    public int $screenIndex = 0;

    public int $nextScreenIndex = 0;

    public int $frame = 0;

    public int $speed = 10_000;

    public Collection $screens;

    public function __construct()
    {
        $this->registerTheme(iPodRenderer::class);

        $this->screens = collect([
            new iPodScreen(
                $this,
                'iPod',
                collect([
                    'Playlists',
                    'Artists',
                    'Albums',
                    'Songs',
                    'Browse',
                    'Extras',
                    'Settings',
                    'Backlight',
                ]),
                collect([
                    'Playlists' => new ListPlaylistsScreen(
                        $this,
                        'Playlists',
                        collect([]),
                        collect([]),
                    ),
                    'Extras' => new iPodScreen(
                        $this,
                        'Extras',
                        collect([
                            'Clock',
                            'Contacts',
                            'Calendar',
                            'Notes',
                            'Photo Import',
                            'Games',
                        ]),
                        collect([
                            'Photo Import' => new iPodScreen(
                                $this,
                                'Photos',
                                collect([
                                    'Import Photos',
                                ]),
                                collect([
                                    'Import Photos' => new ImportPhotosInfo(
                                        $this,
                                        'Import',
                                        collect([
                                            'Import',
                                            'Cancel',
                                        ]),
                                        collect([
                                            'Import' => new ImportingPhotos(
                                                $this,
                                                'Importing',
                                                collect([
                                                    'Stop and Save',
                                                    'Cancel',
                                                ]),
                                                collect([]),
                                                false,
                                            ),
                                        ]),
                                        false,
                                    ),
                                ]),
                            ),
                        ])
                    ),
                ])
            ),
        ]);

        $this->screens->first()->listenForKeys();

        $this->createAltScreen();
    }

    public function __destruct()
    {
        $this->exitAltScreen();
    }

    public function onEnter()
    {
        $screen = $this->screens->get($this->screenIndex);

        $key = $screen->items->get($screen->index);

        if (!$screen->mapping->has($key)) {
            return;
        }

        $nextScreen = $screen->mapping->get($key);

        $this->screens->push($nextScreen);
        $this->nextScreenIndex = $this->screens->keys()->last();

        if (method_exists($nextScreen, 'fetch')) {
            $nextScreen->fetch();
        }

        while ($this->nextScreenIndex !== $this->screenIndex) {
            $this->render();
            usleep($this->speed);
        }

        $this->screens->get($this->screenIndex)->listenForKeys();

        $screen = $this->screens->get($this->screenIndex);

        if ($screen instanceof PlayerScreen) {
            $screen->startedAt = time();

            while (true) {
                $this->render();

                $fh = fopen('php://stdin', 'r');
                $read = [$fh];
                $write = null;
                $except = null;

                if (stream_select($read, $write, $except, 0) === 1) {
                    $key = fread($fh, 10);

                    if (!$screen->handleKey($key)) {
                        break;
                    }
                }

                fclose($fh);

                usleep($this->speed);
            }
        }

        if ($screen instanceof ImportingPhotos) {
            $screen->imported = 0;

            while ($screen->imported < $screen->total) {
                $screen->imported++;
                $this->render();
                usleep($this->speed * 80);
            }

            $this->screens[$this->screenIndex] = new ImportedPhotos($this, 'Import Done', collect([
                'Done',
                'Erase Card',
            ]), false);

            $this->screens[2]->items->push('Roll #1 (6)');
            $this->screens[2]->index = 1;

            $this->screens = $this->screens->filter(fn ($screen) => !$screen instanceof ImportPhotosInfo)->values();

            $this->screenIndex = $this->nextScreenIndex = $this->screens->keys()->last();

            $this->screens->get($this->screenIndex)->listenForKeys();

            $this->render();
        }
    }

    public function onBack()
    {
        $this->nextScreenIndex = max($this->screenIndex - 1, 0);

        while ($this->nextScreenIndex !== $this->screenIndex) {
            $this->render();
            usleep($this->speed);
        }

        $this->screens->get($this->screenIndex)->listenForKeys();

        $this->screens->pop();
    }

    public function value(): bool
    {
        return true;
    }
}
