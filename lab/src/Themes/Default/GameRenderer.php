<?php

namespace ChewieLab\Themes\Default;

use Chewie\Game;
use Chewie\Game\Background;
use Chewie\Game\Player;
use Laravel\Prompts\Themes\Default\Renderer;

class GameRenderer extends Renderer
{
    public function __invoke(Game $prompt): string
    {
        $width = $prompt->terminal()->cols() - 40;
        $height = $prompt->terminal()->lines() - 20;

        $background = $prompt->component(Background::class);

        $xs = collect($background->elements)->map(fn ($el) => $el + ['x_coord' => floor($width * ($el['x'] / 100))])->sortBy('x_coord');

        $els = $xs->map(function ($x, $i) use ($xs, $height) {
            $spaceBefore = $x['x_coord'] - ($xs[$i - 1]['x_coord'] ?? 0);

            $chars = collect(explode(PHP_EOL, $x['char']))->map(fn ($char) => str_repeat(' ', $spaceBefore) . $char);

            while ($chars->count() < $height) {
                $chars->prepend(str_repeat(' ', $spaceBefore + 1));
            }

            return $chars->map(fn ($c) => $this->{$x['color']}($c));
        });

        // $player = $prompt->component(Player::class);

        // $playerChar = '🧍';

        // $playerCol = collect(range(0, $height))->map(fn ($i) => ' ');

        // if ($player->value('height') > 0) {
        //     // dd("player height is {$player->value('height')}");
        // }

        // $playerCol->splice($height - $player->value('height'), $player->value('height'), $playerChar);

        $notEmpty = $els->isNotEmpty();

        // if ($els->isNotEmpty()) {
        //     $els->dd();
        // }

        // while ($els->count() < $width) {
        //     $els->prepend(collect(range(0, $height))->map(fn ($i) => ' '));
        // }

        if ($notEmpty) {
            $els->dd();
        }
        // $els->prepend($playerCol);

        // $els->splice(0, 1, $playerCol);

        if ($notEmpty) {

            // dd(collect($els->shift())->zip( ...$els))->map(fn ($line) => $line->implode(''))->dd()->implode(PHP_EOL));
        }

        collect($els->shift())->zip(...$els)->each(fn ($line) => $this->line($line->implode('')));

        return $this;
    }
}
