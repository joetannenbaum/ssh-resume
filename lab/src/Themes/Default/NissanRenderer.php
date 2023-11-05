<?php

namespace ChewieLab\Themes\Default;

use Chewie\Concerns\DrawsBigNumbers;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Output\Lines;
use Chewie\Output\Util;
use ChewieLab\Nissan;
use ChewieLab\Nissan\Battery;
use ChewieLab\Nissan\EngineTemp;
use ChewieLab\Nissan\Fuel;
use ChewieLab\Nissan\OilLevel;
use ChewieLab\Nissan\Rpm;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class NissanRenderer extends Renderer
{
    use DrawsBigNumbers;
    use DrawsBoxes;
    use DrawsHotkeys;

    protected $gaugeHeight = 12;

    public function __invoke(Nissan $prompt): string
    {
        $fuel = $this->fuelLines($prompt);
        $rpm = $this->rpmLines($prompt);
        $engineTemp = $this->engineTempLines($prompt);
        $oilLevel = $this->oilLevelLines($prompt);
        $battery = $this->batteryLines($prompt);

        Lines::fromColumns([$fuel, $rpm, $engineTemp, $oilLevel, $battery])
            ->alignBottom()
            ->spacing(1)
            ->lines()
            ->each(fn ($line) => $this->line($line));

        $this->newLine();
        $this->newLine();

        $this->hotkey('Enter', $prompt->carStarted ? 'Turn off Nissan' : 'Turn on Nissan');

        if ($prompt->carStarted) {
            $this->hotkey('Space', 'Rev Engine');
        }

        collect($this->hotkeys())->each(fn ($hotkey) => $this->line(' ' . $hotkey));

        return $this;
    }

    protected function rpmLines(Nissan $prompt)
    {
        /** @var Rpm $rpm */
        $rpm = $prompt->loopable(Rpm::class);

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
        $factor = $rpm->multiplier->current() * (1 / $barCount);
        $offset = ($rpm->multiplier->current() - 1) % $barCount;

        $markerOffset = 3;

        $allBars = collect($bars);

        while ($allBars->count() < $totalColumns + $barCount) {
            $allBars->push(...$bars);
        }

        $up = $allBars->slice($offset)->values()->filter(fn ($bar, $i) => $i < $startRedAt);

        $setCounter = 0;
        $counter = 1;

        $curve = <<<'CURVE'
                                                                ¸¸__..--ˆˆ--..__¸¸
                                                      ¸¸__..--ˆˆ                  ˆˆ--
                                            ¸¸__..--ˆˆ
                                  ¸¸__..--ˆˆ
                        ¸¸__..--ˆˆ
              ¸¸__..--ˆˆ
        ..--ˆˆ
        CURVE;

        $curve = collect(explode("\n", $curve))->map(fn ($l) => mb_str_split($l));

        $rpms = $this->bigNumber($rpm->rpms->current())->map(fn ($l) => collect(mb_str_split($l)))->map(function ($l) {
            while ($l->count() < 4) {
                $l->push(' ');
            }

            return $l;
        });

        $rpms->last()->push(...collect(str_split(' x 100r/min'))->map(fn ($i) => $this->white($i)));

        $rpms = $rpms->map(function ($l) {
            while ($l->count() < 15) {
                $l->push(' ');
            }

            return $l;
        })->map(fn ($l) => $l->map(fn ($i) => $this->green($i)));

        $rpmsColumns = collect($rpms->shift())->zip(...$rpms);

        $curveColumns = collect($curve->shift())
            ->zip(...$curve)
            ->map(
                fn ($l) => $l->reverse()
                    ->skipWhile(fn ($i) => $i === null || $i === ' ')
                    ->reverse()
                    ->map(fn ($i) => $i ?? ' ')
            )
            ->map(function (Collection $l, $i) use (&$rpmsColumns) {
                if ($i > 0 && $rpmsColumns->count() > 0) {
                    $nextRpms = $rpmsColumns->shift();
                    $l->splice(0, $nextRpms->count(), $nextRpms);
                }

                return $l;
            });

        $lines->push(Util::range($highest)->map(fn ($l) => ' '));

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
                    ->map(fn ($l) => $rpm->value->current() === $counter ? $l : $this->dim($l));

                $lines->push($col);
                $counter++;
            }

            $setCounter++;
        }

        $setCounter--;

        foreach ($up->slice(-13, 6)->reverse()->chunkWhile(fn ($i) => $i !== '█') as $setIndex => $set) {
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
                    ->map(fn ($l) => $rpm->value->current() === $counter ? $l : $this->dim($l));

                $lines->push($col);

                $counter++;
            }

            $setCounter--;
        }

        $leftCap = floor($highest * .7);
        $leftBorder = Util::range($highest)->map(fn ($i) => $i > $leftCap ? '┃' : ' ')->concat(['┗'])->map(fn ($l) => $prompt->carStarted ? $l : $this->dim($l));

        $rightCap = floor($highest * .25);
        $rightBorder = Util::range($highest)->map(fn ($i) => $i > $rightCap ? '┃' : ' ')->concat(['┛'])->map(fn ($l) => $prompt->carStarted ? $l : $this->dim($l));

        $lines = $lines->map(function (Collection $l, $i) use ($prompt, $curveColumns) {
            $colWidth = mb_strlen($this->stripEscapeSequences($l->first()));

            $curveLine = collect();

            while ($curveLine->count() < $colWidth) {
                $curveLine->push($curveColumns->shift());
            }

            $max = $curveLine->map(fn ($l) => $l->count())->max();

            $curveLine = $curveLine->map(function ($l) use ($max) {
                $l->push(...array_fill(0, $max - $l->count(), ' '));

                return $l;
            });

            $curveLine = $curveLine->count() === 1
                ? $curveLine->first()
                : collect($curveLine->shift())->zip(...$curveLine)->map(fn ($c) => $c->implode(''));

            $l->splice(0, $curveLine->count(), $curveLine->map(fn ($i) => mb_strpos($i, '\\') !== false ? $i : $this->red($i))->map(fn ($i) => $prompt->carStarted ? $this->bold($i) : $this->dim($i)));

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
            function ($l, $i) use (&$markers, $prompt) {
                if ($this->stripEscapeSequences($l->get($l->count() - 2)) === '█ ') {
                    $str = array_shift($markers);

                    if (!$prompt->carStarted) {
                        $str = $this->dim($str);
                    }

                    return $l->push($str . ' ');
                }

                $colWidth = mb_strlen($this->stripEscapeSequences($l->first()));

                return $l->push(str_repeat(' ', $colWidth));
            }
        );

        $lines = $lines->map(function ($l) {
            $l->prepend(str_repeat(' ', mb_strlen($this->stripEscapeSequences($l->first()))));

            return $l;
        });

        return collect($lines->shift())->zip(...$lines)->map(fn ($line) =>  str_repeat(' ', 5) . $line->implode('') . str_repeat(' ', 5));
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
        /** @var Battery $gauge */
        $gauge = $prompt->loopable(Battery::class);

        $top = '┏ ' . $this->bold('   ');
        $belowTop = '┣ ' . $this->bold('90 ');
        $aboveBottom = '┣ ' . $this->bold('10 ');
        $bottom = '┗ ' . $this->bold('   ');
        $default = '┃' . str_repeat(' ', 4);

        return $this->gaugeLines(
            $gauge->value->current(),
            16,
            fn ($i) => match ($i) {
                $this->gaugeHeight     => $prompt->carStarted ? $top : $this->dim($top),
                $this->gaugeHeight - 1 => $prompt->carStarted ? $belowTop : $this->dim($belowTop),
                2                      => $prompt->carStarted ? $aboveBottom : $this->dim($aboveBottom),
                1                      => $prompt->carStarted ? $bottom : $this->dim($bottom),
                default                => $prompt->carStarted ? $default : $this->dim($default),
            },
            'BATTERY',
        );
    }

    protected function oilLevelLines(Nissan $prompt): Collection
    {
        /** @var OilLevel $gauge */
        $gauge = $prompt->loopable(OilLevel::class);

        $ninety = '┏ ' . $this->bold('90 ');
        $fortyFive = '┣ ' . $this->bold('45 ');
        $zero = '┗ ' . $this->bold('0  ');
        $default = '┃' . str_repeat(' ', 4);

        return $this->gaugeLines(
            $gauge->value->current(),
            16,
            fn ($i) => match ($i) {
                $this->gaugeHeight     => $prompt->carStarted ? $ninety : $this->dim($ninety),
                $this->gaugeHeight / 2 => $prompt->carStarted ? $fortyFive : $this->dim($fortyFive),
                1                      => $prompt->carStarted ? $zero : $this->dim($zero),
                default                => $prompt->carStarted ? $default : $this->dim($default),
            },
            'OIL LEVEL',
        );
    }

    protected function fuelLines(Nissan $prompt): Collection
    {
        /** @var Fuel $gauge */
        $gauge = $prompt->loopable(Fuel::class);

        $full = '┏ ' . $this->bold('F  ');
        $half = '┣ ' . $this->bold('½  ');
        $empty = '┗ ' . $this->bold('E  ');
        $default = '┃' . str_repeat(' ', 4);

        return $this->gaugeLines(
            $gauge->value->current(),
            16,
            fn ($i) => match ($i) {
                $this->gaugeHeight     => $prompt->carStarted ? $full : $this->dim($full),
                $this->gaugeHeight / 2 => $prompt->carStarted ? $half : $this->dim($half),
                1                      => $prompt->carStarted ? $empty : $this->dim($empty),
                default                => $prompt->carStarted ? $default : $this->dim($default),
            },
            'FUEL',
            true,
        );
    }

    protected function engineTempLines(Nissan $prompt): Collection
    {
        /** @var EngineTemp $gauge */
        $gauge = $prompt->loopable(EngineTemp::class);

        $top = '┏ ' . $this->bold('270');
        $bottom = '┗ ' . $this->bold('120');
        $aboveBottom = '┣' . str_repeat(' ', 4);
        $default = '┃' . str_repeat(' ', 4);

        return $this->gaugeLines(
            $gauge->value->current(),
            16,
            fn ($i) => match ($i) {
                $this->gaugeHeight => $prompt->carStarted ? $top : $this->dim($top),
                2, $this->gaugeHeight - 1 => $prompt->carStarted ? $aboveBottom : $this->dim($aboveBottom),
                1       => $prompt->carStarted ? $bottom : $this->dim($bottom),
                default => $prompt->carStarted ? $default : $this->dim($default),
            },
            'ENGINE TEMP',
        );
    }

    protected function gaugeLines(int $value, int $width, $rightSideMatcher, $label, bool $fill = false): Collection
    {
        $lines = collect();

        $char = '▆';

        $label = ' ' . str_pad($label, $width - 1);

        $lines->push($this->prompt->carStarted ? $label : $this->dim($label));

        $topLine = $this->green('┌' . str_repeat('─', $width - 2) . '┐');

        $lines->push($this->prompt->carStarted ? $topLine : $this->dim($topLine));

        $sideLine = $this->prompt->carStarted ? '│' : $this->dim('│');

        foreach (range($this->gaugeHeight, 1) as $i) {
            $rightSide = $rightSideMatcher($i);

            $bar = str_repeat($char, $width - 8);

            if ($fill) {
                $bar = $i < $value ? $bar : $this->dim($bar);
            } else {
                $bar = $i === $value ? $bar : $this->dim($bar);
            }

            $lines->push($this->green($sideLine . $bar . $rightSide . ' ' . $sideLine));
        }

        return $lines;
    }
}
