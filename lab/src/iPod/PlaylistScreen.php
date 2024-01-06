<?php

namespace ChewieLab\iPod;

use Chewie\KeyPressListener;
use ChewieLab\iPod;
use ChewieLab\PlayerScreen;
use ChewieLab\Spotify;
use Illuminate\Support\Collection;
use Laravel\Prompts\Key;

class PlaylistScreen
{
    public int $index = 0;

    public string $id;

    public function __construct(
        protected iPod $ipod,
        public string $title,
        public Collection $items,
        public Collection $mapping,
        public bool $arrows = true,
    ) {
    }

    public function visible()
    {
        if ($this->index < 8) {
            return $this->items->slice(0, 8);
        }

        return $this->items->slice($this->index - 7, 8);
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function fetch(): static
    {
        collect(Spotify::get()->getPlaylist($this->id)->tracks->items)->each(function ($track) {
            $this->items->push($track->track->name);
            $this->mapping->put(
                $track->track->name,
                (new PlayerScreen($this->ipod, 'Now Playing', collect([]), collect([])))->setTrack($track),
            );
        });

        return $this;
    }

    public function listenForKeys()
    {
        KeyPressListener::for($this->ipod)
            ->clearExisting()
            ->on(
                [Key::UP, Key::UP_ARROW],
                fn () => $this->index = max($this->index - 1, 0),
            )
            ->on(
                [Key::DOWN, Key::DOWN_ARROW],
                fn () => $this->index = min($this->index + 1, $this->items->count() - 1),
            )
            ->on(
                [Key::LEFT, Key::LEFT_ARROW],
                fn () => $this->ipod->onBack(),
            )
            ->on(
                [Key::ENTER],
                fn () => $this->ipod->onEnter(),
            )
            ->listen();
    }
}
