<?php

namespace ChewieLab\Themes\Default;

use Chewie\Concerns\Aligns;
use Chewie\ImportedPhotos;
use Chewie\ImportingPhotos;
use Chewie\ImportPhotosInfo;
use Chewie\iPod;
use Illuminate\Support\Collection;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class iPodRendererOrig extends Renderer
{
    use Aligns;
    use DrawsBoxes;

    public function __invoke(iPod $ipod): string
    {
        $output = $this->renderScreen($ipod);

        $output->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function renderScreen(iPod $ipod): Collection
    {

        $width = 30;

        $this->minWidth = $width;

        $currentScreen = $ipod->screens->get($ipod->screenIndex);
        $nextScreen = $ipod->screens->get($ipod->nextScreenIndex);

        $line = $this->centerHorizontally($nextScreen->title, $width)->first();

        $battery = str_repeat('▅', 3) . '▪';

        $line = mb_substr($line, 0, $width - (mb_strlen($battery) + 1)) . ' ' . $battery;

        $lines = collect([$this->bold($line)]);

        $lines->push(str_repeat('─', $width));

        $currentScreenLines = $this->getScreenAtIndex($ipod, $ipod->screenIndex, $width);
        $currentScreenMenuStartIndex = $currentScreenLines->search(fn ($line) => $currentScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false);

        if ($ipod->screenIndex === $ipod->nextScreenIndex) {
            $currentScreenLines
                ->map(fn ($line) => $currentScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false ? $this->bold($line) : $line)
                ->map(fn ($line, $index) =>  $index - $currentScreenMenuStartIndex === $currentScreen->index ? $this->inverse($line) : $line)
                ->each(fn ($line) => $lines->push($line));

            $this->box('', $lines->implode(PHP_EOL));

            $output = $this->output;

            $this->output = '';

            return collect(explode(PHP_EOL, $output));
        }

        $nextScreenLines = $this->getScreenAtIndex($ipod, $ipod->nextScreenIndex, $width);

        $goingForward = $ipod->screenIndex < $ipod->nextScreenIndex;

        $nextScreenMenuStartIndex = $nextScreenLines->search(fn ($line) => $nextScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false);

        $currentBoldLines = $currentScreenLines->filter(fn ($line) => $currentScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false)->keys();
        $nextBoldLines = $nextScreenLines->filter(fn ($line) => $nextScreen->items->contains(trim($line)) || mb_strpos($line, '>') !== false)->keys();

        $currentScreenLines = $currentScreenLines->map(fn ($line) => $goingForward ? mb_substr($line, $ipod->frame) : mb_substr($line, 0, $width - $ipod->frame))
            ->map(fn ($line, $index) =>  $index - $currentScreenMenuStartIndex === $currentScreen->index ? $this->inverse($line) : $line)
            ->map(fn ($line, $index) => $currentBoldLines->contains($index) ? $this->bold($line) : $line);

        $nextScreenLines = $nextScreenLines->map(fn ($line) => $goingForward ? mb_substr($line, 0, $ipod->frame) : mb_substr($line, $width - $ipod->frame))
            ->map(fn ($line, $index) => $index - $nextScreenMenuStartIndex === $nextScreen->index ? $this->inverse($line) : $line)
            ->map(fn ($line, $index) => $nextBoldLines->contains($index) ? $this->bold($line) : $line);

        $screenLines = $goingForward ? $currentScreenLines->zip($nextScreenLines) : $nextScreenLines->zip($currentScreenLines);

        $screenLines->map(fn ($lines) => $lines->implode(''))
            ->each(fn ($line) => $lines->push($line));

        $ipod->frame++;

        if ($ipod->frame === $width) {
            $ipod->frame = 0;
            $ipod->screenIndex = $ipod->nextScreenIndex;
        }

        $this->box('', $lines->implode(PHP_EOL));

        $output = $this->output;

        $this->output = '';

        return collect(explode(PHP_EOL, $output));
    }

    protected function getScreenAtIndex($ipod, $screenIndex, $width): Collection
    {
        $screen = $ipod->screens->get($screenIndex);

        $lines = $screen->items->map(fn ($item) => str_pad(' ' . $item, $width - 2, ' ', STR_PAD_RIGHT) . ($screen->arrows ? '> ' : '  '));

        if ($screen instanceof ImportPhotosInfo) {
            $infoLines = $this->getImportPhotosInfoLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        if ($screen instanceof ImportingPhotos) {
            $infoLines = $this->getImportingPhotosLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        if ($screen instanceof ImportedPhotos) {
            $infoLines = $this->getCompletedImportLines($screen, $width);

            $infoLines->push(str_repeat('─', $width));

            $lines = $infoLines->merge($lines);
        }

        while ($lines->count() < 8) {
            $lines->push(str_repeat(' ', $width));
        }

        return $lines;
    }

    protected function getImportPhotosInfoLines(ImportPhotosInfo $screen, $width): Collection
    {
        return collect([
            '',
            '  Type: Media card',
            'Photos: 6',
            '  Free: 62.6 MB of 62.9 M',
            '',
        ])->map(fn ($line) => str_repeat(' ', 4) . $line)->map(fn ($line) => str_pad($line, $width - 2, ' ', STR_PAD_RIGHT));
    }

    protected function getCompletedImportLines(ImportedPhotos $screen, $width): Collection
    {
        return collect([
            '',
            '    Type: Media card',
            'Imported: 6 of 6',
            '    Free: 62.6 MB of 62.9 M',
            '',
        ])->map(fn ($line) => str_repeat(' ', 2) . $line);
    }

    protected function getImportingPhotosLines(ImportingPhotos $screen, $width): Collection
    {
        $barWidth = $width - 6;
        $filledPercent = floor(($screen->imported / $screen->total) * $barWidth);
        $emptyPercent = $barWidth - $filledPercent;
        $bar = str_repeat('▓', $filledPercent) . str_repeat('░', $emptyPercent);
        $lines = collect([
            '',
            $bar,
            // "{$screen->imported} of {$screen->total}",
            $this->bold("{$screen->imported} of {$screen->total}"),
            // $screen->imported % 2 === 0 ? '' : 'Importing',
            $screen->imported % 2 === 0 ? '' : $this->bold('Importing'),
            '',
        ]);

        return $this->centerHorizontally($lines, $width);
    }
}
