<?php

namespace Chewie\Concerns;

use Chewie\Output\Util;
use Illuminate\Support\Collection;

trait Aligns
{
    protected function centerHorizontally(string|iterable $lines, int $width): Collection
    {
        $lines = $this->toCollection($lines);

        $lineLengths = $lines->map(fn ($line) => mb_strlen(Util::stripEscapeSequences($line)));

        $maxLineLength = $lineLengths->max();

        $basePadding = floor(($width - $maxLineLength) / 2);

        $result = $lines->map(function ($line) use ($basePadding, $maxLineLength) {
            $lineLength = mb_strlen(Util::stripEscapeSequences($line));
            $padding = $basePadding + floor((($maxLineLength - $lineLength) / 2));

            return str_repeat(' ', $padding) . $line . str_repeat(' ', $padding);
        });

        $maxLine = $result->max(fn ($line) => mb_strlen(Util::stripEscapeSequences($line)));

        return $result->map(function ($line) use ($maxLine) {
            $lineLength = mb_strlen(Util::stripEscapeSequences($line));

            return $line . str_repeat(' ', $maxLine - $lineLength);
        });
    }

    protected function spaceBetween(int $width, string ...$items)
    {
        $totalLength = collect($items)->map(fn ($item) => mb_strlen(Util::stripEscapeSequences($item)))->sum();
        $space = $width - $totalLength;
        $spacePerItem = floor($space / (count($items) - 1));

        $result = '';

        foreach ($items as $i => $item) {
            $result .= $item;

            if ($i < count($items) - 1) {
                $result .= str_repeat(' ', $spacePerItem);
            }
        }

        return $result;
    }

    protected function centerVertically(string|iterable $lines, int $height): Collection
    {
        $lines = $this->toCollection($lines);
        $paddingTop = floor(($height / 2)) - floor($lines->count() / 2);

        foreach (Util::range($paddingTop) as $i) {
            $lines->prepend('');
            $lines->push('');
        }

        return $lines;
    }

    protected function center(string|iterable $lines, int $width, int $height): Collection
    {
        return $this->centerVertically($this->centerHorizontally($lines, $width), $height);
    }

    protected function toCollection(string|iterable $lines): Collection
    {
        $lines = is_string($lines) ? explode(PHP_EOL, $lines) : $lines;

        return collect($lines);
    }
}
