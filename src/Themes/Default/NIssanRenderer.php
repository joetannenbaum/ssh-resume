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
        $totalColumns = 38;
        $highest = 11;
        $startRedAt = $totalColumns - 6;
        $factor = $rpm->multiplier * (1 / $barCount);
        $offset = ($rpm->multiplier - 1) % $barCount;

        $markerOffset = 3;

        $allBars = collect($bars);

        while ($allBars->count() < $totalColumns + $barCount) {
            $allBars->push(...$bars);
        }

        $up = $allBars->slice($offset)->values()->filter(fn ($bar, $i) => $i < $startRedAt);

        $setCounter = 0;
        $counter = 1;

        $curve = <<<CURVE
                                                                ¸¸__..--ˆˆ--..__¸¸
                                                      ¸¸__..--ˆˆ                  ˆˆ--
                                            ¸¸__..--ˆˆ
                                  ¸¸__..--ˆˆ
                        ¸¸__..--ˆˆ
              ¸¸__..--ˆˆ
        ..--ˆˆ
        CURVE;

        $curve = collect(explode("\n", $curve))->map(fn ($l) => mb_str_split($l));

        $curveColumns = collect($curve->shift())
            ->zip(...$curve)
            ->map(
                fn ($l) => $l->reverse()
                    ->skipWhile(fn ($i) => $i === null || $i === ' ')
                    ->reverse()
                    ->map(fn ($i) => $i ?? ' ')
            );

        $lines->push(collect(range(1, $highest))->map(fn ($l) => ' '));

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
                    ->map(fn ($l) => mb_str_pad($l, 2))
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
                    ->map(fn ($l) => mb_str_pad($l, 2))
                    ->map(fn ($l) => $counter > $startRedAt ? $this->red($l) : $this->green($l))
                    ->map(fn ($l) => $rpm->value === $counter ? $l : $this->dim($l));

                $lines->push($col);

                $counter++;
            }

            $setCounter--;
        }

        $leftCap = floor($highest * .7);
        $leftBorder = collect(range(1, $highest))->map(fn ($i) => $i > $leftCap ? '┃' : ' ')->concat(['┗'])->map(fn ($l) => $prompt->carStarted ? $l : $this->dim($l));

        $rightCap = floor($highest * .25);
        $rightBorder = collect(range(1, $highest))->map(fn ($i) => $i > $rightCap ? '┃' : ' ')->concat(['┛'])->map(fn ($l) => $prompt->carStarted ? $l : $this->dim($l));

        $lines = $lines->map(function (Collection $l) use ($prompt, $curveColumns) {
            $colWidth = mb_strlen($this->stripEscapeSequences($l->first()));

            $curveLine = collect();

            while ($curveLine->count() < $colWidth) {
                $curveLine->push($curveColumns->shift());
            }

            $curveLine = $curveLine->count() === 1 ? $curveLine->first() : collect($curveLine->shift())->zip(...$curveLine)->map(fn ($c) => $c->implode(''));

            $curveLine = $curveLine->map(fn ($i) => $this->bold($this->red($i)));

            $l->splice(0, $curveLine->count(), $curveLine->map(fn ($i) => $prompt->carStarted ? $i : $this->dim($i)));

            $str = mb_str_pad('', $colWidth, '━');

            if (!$prompt->carStarted) {
                $str = $this->dim($str);
            }

            $l->push($str);

            return $l;
        });

        $lines->prepend($leftBorder);
        $lines->push($rightBorder);

        $lines = $lines->map(
            function ($l, $i) use (&$markers) {
                if ($this->stripEscapeSequences($l->get($l->count() - 2)) === '█ ') {
                    return $l->push(array_shift($markers));
                }

                return $l->push(' ');
            }
        );

        return collect($lines->shift())->zip(...$lines)->map(fn ($line) =>  $line->implode(''));
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

            $col = $col->reverse()->values()->map(fn ($l) => mb_str_pad($l, 2));

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
