<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Themes\Default\iPodRenderer;
use Illuminate\Support\Collection;
use Laravel\Prompts\Prompt;
use SpotifyWebAPI\SpotifyWebAPI;

class iPodOrig extends Prompt
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
        // $api = new SpotifyWebAPI();

        // $api->setAccessToken(file_get_contents(__DIR__ . '/../../spotify/access_token.txt'));

        // dd($api->getMyPlaylists());

        $this->registerTheme(iPodRenderer::class);

        $this->screens = collect([
            new iPodScreen($this, 'iPod', collect([
                'Playlists',
                'Artists',
                'Albums',
                'Songs',
                'Browse',
                'Extras',
                'Settings',
                'Backlight',
            ])),
            new iPodScreen($this, 'Extras', collect([
                'Clock',
                'Contacts',
                'Calendar',
                'Notes',
                'Photo Import',
                'Games',
            ])),
            new iPodScreen($this, 'Photos', collect([
                'Import Photos',
            ])),
            new ImportPhotosInfo($this, 'Import', collect([
                'Import',
                'Cancel',
            ]), false),
            new ImportingPhotos(
                $this,
                'Importing',
                collect([
                    'Stop and Save',
                    'Cancel',
                ]),
                false
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
        $this->nextScreenIndex = min($this->screenIndex + 1, $this->screens->count() - 1);

        while ($this->nextScreenIndex !== $this->screenIndex) {
            $this->render();
            usleep($this->speed);
        }

        $this->screens->get($this->screenIndex)->listenForKeys();

        if ($this->screens->get($this->screenIndex) instanceof ImportingPhotos) {
            $screen = $this->screens->get($this->screenIndex);

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
    }

    public function value(): bool
    {
        return true;
    }
}
