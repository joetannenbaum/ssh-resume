<?php

namespace Chewie\Themes\Default;

use Chewie\Nissan;
use Chewie\Nissan\Battery;
use Chewie\Nissan\EngineTemp;
use Chewie\Nissan\Fuel;
use Chewie\Nissan\OilLevel;
use Chewie\Nissan\Rpm;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class NissanRenderer extends Renderer
{
    use DrawsBoxes;

    public function __invoke(Nissan $prompt): string
    {
        $rpm = $this->rpmLines($prompt);

        $rpm->each(fn ($line) => $this->line($line));

        return $this;

        $fuel = $this->fuelLines($prompt);
        $rpm = $this->rpmLines($prompt);
        $engineTemp = $this->engineTempLines($prompt);
        $oilLevel = $this->oilLevelLines($prompt);
        $battery = $this->batteryLines($prompt);

        $fuel->zip($rpm, $engineTemp, $oilLevel, $battery)->map(fn ($lines) => $lines->implode(' '))->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function rpmLines(Nissan $prompt)
    {
        $rpm = $prompt->components[Rpm::class];

        $lines = collect();

        $fullBar = '█';

        $bars = [
            '▁',
            '▂',
            '▃',
            '▄',
            '▅',
            '▆',
            '▇',
            '█',
        ];

        $markers = [
            '½',
            '1',
            '2',
            '3',
            '4',
            '5',
            '6',
            '7',
        ];

        $barCount = count($bars);
        $columns = 38;
        $highest = 30;
        $startRedAt = $columns - 6;
        $factor = $rpm->multiplier * (1 / $barCount);
        $offset = ($rpm->multiplier - 1) % $barCount;

        $markerOffset = 3;

        $allBars = collect($bars);

        while ($allBars->count() < $columns + $barCount) {
            $allBars->push(...$bars);
        }

        $up = $allBars->slice($offset)->values()->filter(fn ($bar, $i) => $i < $startRedAt);

        $setCounter = 0;
        $counter = 1;

        foreach ($up->chunkWhile(fn ($i) => $i !== '▁') as $setIndex => $set) {
            foreach ($set as $index => $bar) {
                $col = collect();

                $whole = $setCounter + ceil($factor);

                if ($counter === $markerOffset || ($counter - $markerOffset) % 5 === 0) {
                    $col->push($fullBar);
                } else {
                    $col->push(' ');
                }

                while ($whole > 0) {
                    $col->push($fullBar);
                    $whole--;
                }

                $col->push($bar);


                while ($col->count() < $highest) {
                    $col->push(' ');
                }

                $col = $col->reverse()->values()
                    ->map(fn ($l) => $counter > $startRedAt ? $this->red($l) : $this->green($l))
                    ->map(fn ($l) => $rpm->value === $counter ? $l : $this->dim($l));

                $lines->push($col);
                $counter++;
            }

            $setCounter++;
        }

        $setCounter--;

        foreach ($up->slice(-13, 6)->reverse()->chunkWhile(fn ($i) => $i !== '█') as $setIndex => $set) {
            foreach ($set as $index => $bar) {
                $col = collect();

                $whole = $setCounter  + ceil($factor);

                if ($counter === $markerOffset || ($counter - $markerOffset) % 5 === 0) {
                    $col->push($fullBar);
                } else {
                    $col->push(' ');
                }

                while ($whole > 0) {
                    $col->push($fullBar);
                    $whole--;
                }

                $col->push($bar);


                while ($col->count() < $highest) {
                    $col->push(' ');
                }

                $col = $col->reverse()->values()
                    ->map(fn ($l) => $counter > $startRedAt ? $this->red($l) : $this->green($l))
                    ->map(fn ($l) => $rpm->value === $counter ? $l : $this->dim($l));

                $lines->push($col);

                $counter++;
            }

            $setCounter--;
        }

        $leftCap = floor($highest * .75);
        $leftBorder = collect(range(1, $highest))->map(fn ($i) => $i > $leftCap ? '┃' : ' ');

        $rightCap = floor($highest * .5);
        $rightBorder = collect(range(1, $highest))->map(fn ($i) => $i > $rightCap ? '┃' : ' ');

        $lines = $lines->map(fn ($l) => $l->push('━'));

        $lines->prepend($leftBorder->concat(['┗']));
        $lines->push($rightBorder->concat(['┛']));

        $lines = $lines->map(
            function ($l, $i) use (&$markers) {
                if ($this->stripEscapeSequences($l->get($l->count() - 2)) === '█') {
                    return $l->push(array_shift($markers));
                }

                return $l->push(' ');
            }
        );

        return collect($lines->shift())->zip(...$lines)->map(fn ($line) => ' ' . $line->implode($line[1] == '━' ? '━' : ' ') . ' ');
    }

    protected function generateRpmColumn(Collection $bars, $counter): Collection
    {
        $lines = collect();

        foreach ($bars as $bar) {
            $col = collect();

            $whole = $counter;

            while ($whole > 0) {
                $col->push('█');
                $whole--;
            }

            $col->push($bar);

            while ($col->count() < 40) {
                $col->push(' ');
            }

            $col = $col->reverse()->values();

            $lines->push($col);
        }

        return $lines;
    }

    protected function batteryLines(Nissan $prompt): Collection
    {
        $gauge = $prompt->components[Battery::class];

        return $this->gaugeLines(
            $gauge->value,
            16,
            fn ($i) => match ($i) {
                20 => '┏ ' . $this->bold('   '),
                19 => '┣ ' . $this->bold('90 '),
                2 => '┣ ' . $this->bold('10 '),
                1 => '┗ ' . $this->bold('   '),
                default => '┃' . str_repeat(' ', 4),
            },
        );
    }

    protected function oilLevelLines(Nissan $prompt): Collection
    {
        $gauge = $prompt->components[OilLevel::class];

        return $this->gaugeLines(
            $gauge->value,
            16,
            fn ($i) => match ($i) {
                20 => '┏ ' . $this->bold('90 '),
                10 => '┣ ' . $this->bold('45 '),
                1 => '┗ ' . $this->bold('0  '),
                default => '┃' . str_repeat(' ', 4),
            },
        );
    }

    protected function fuelLines(Nissan $prompt): Collection
    {
        $gauge = $prompt->components[Fuel::class];

        return $this->gaugeLines(
            $gauge->value,
            16,
            fn ($i) => match ($i) {
                20 => '┏ ' . $this->bold('F  '),
                10 => '┣ ' . $this->bold('½  '),
                1 => '┗ ' . $this->bold('E  '),
                default => '┃' . str_repeat(' ', 4),
            },
            true,
        );
    }

    protected function engineTempLines(Nissan $prompt): Collection
    {
        $gauge = $prompt->components[EngineTemp::class];

        return $this->gaugeLines(
            $gauge->value,
            16,
            fn ($i) => match ($i) {
                20 => '┏ ' . $this->bold('270'),
                2, 19 => '┣' . str_repeat(' ', 4),
                1 => '┗ ' . $this->bold('120'),
                default => '┃' . str_repeat(' ', 4),
            },
        );
    }

    protected function gaugeLines(int $value, int $width, $rightSideMatcher, bool $fill = false): Collection
    {
        $lines = collect();

        $char = '▆';

        $lines->push($this->dim($this->green('┌' . str_repeat('─', $width - 2) . '┐')));

        foreach (range(20, 1) as $i) {
            $rightSide = $rightSideMatcher($i);

            $bar = str_repeat($char, $width - 8);

            if ($fill) {
                $bar = $i < $value ? $bar : $this->dim($bar);
            } else {
                $bar = $i === $value ? $bar : $this->dim($bar);
            }

            $lines->push($this->green($this->dim('│') . $bar . $rightSide  . $this->dim(' │')));
        }

        return $lines;
    }
}
