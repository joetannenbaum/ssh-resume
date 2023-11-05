<?php

namespace ChewieLab\Themes\Default;

use Chewie\Concerns\DrawsHotkeys;
use Chewie\Lister;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class ListerRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsScrollbars;

    public function __invoke(Lister $prompt): string
    {
        $totalWidth = $prompt->terminal()->cols() - 6;

        $fileListWidth = $prompt->showActivityLog ? (int) floor($totalWidth * .7) : $totalWidth;
        $activityLogWidth = $totalWidth - $fileListWidth - 2;
        $height = $prompt->terminal()->lines() - 6;

        $buffered = new BufferedConsoleOutput();

        $tableStyle = (new TableStyle())
            ->setHorizontalBorderChars('')
            ->setVerticalBorderChars('', '')
            ->setCellHeaderFormat($this->dim('<fg=default>%s</>'))
            ->setCellRowFormat('<fg=default>%s</>');

        $tableStyle->setCrossingChars('', '', '', '', '', '</>', '', '', '', '<fg=gray>', '', '');

        $rows = $prompt->items->map(function ($item) use ($prompt) {
            $pastVersions = $prompt->versions->map(
                fn ($version) => $version->firstWhere('name', $item['name']),
            )->filter();

            if ($prompt->versions->count() > 0 && $pastVersions->count() < $prompt->versions->count() / 2) {
                // This file just showed up
                return [
                    $this->green($item['permissions']),
                    $this->green($item['owner']),
                    $this->green($item['group']),
                    $this->green($item['size']),
                    $this->green($item['date']),
                    $this->green($item['name']),
                ];
            }

            $permissionsChanged = $pastVersions->filter(
                fn ($version) => $version['permissions'] !== $item['permissions'],
            )->isNotEmpty();

            $ownerChanged = $pastVersions->filter(
                fn ($version) => $version['owner'] !== $item['owner'],
            )->isNotEmpty();

            $groupChanged = $pastVersions->filter(
                fn ($version) => $version['group'] !== $item['group'],
            )->isNotEmpty();

            $sizeChanged = $pastVersions->filter(
                fn ($version) => $version['size'] !== $item['size'],
            )->isNotEmpty();

            $dateChanged = $pastVersions->filter(
                fn ($version) => $version['date'] !== $item['date'],
            )->isNotEmpty();

            return [
                $permissionsChanged ? $this->yellow($item['permissions']) : $this->dim($item['permissions']),
                $ownerChanged ? $this->yellow($item['owner']) : $item['owner'],
                $groupChanged ? $this->yellow($item['group']) : $item['group'],
                $sizeChanged ? $this->yellow($item['size']) : $this->dim($item['size']),
                $dateChanged ? $this->yellow($item['date']) : $this->dim($item['date']),
                ($item['is_dir'] ? $this->cyan($item['name']) : $item['name']),
            ];
        });

        (new Table($buffered))
            ->setRows($rows->toArray())
            ->setStyle($tableStyle)
            ->render();

        $fileLines = collect(explode(PHP_EOL, trim($buffered->content(), PHP_EOL)))->map(function ($line) use ($fileListWidth) {
            $lineLength = mb_strlen($this->stripEscapeSequences($line));

            return $line . str_repeat(' ', max($fileListWidth - $lineLength, 0));
        });

        while ($fileLines->count() < $height) {
            $fileLines->push(str_repeat(' ', $fileListWidth));
        }

        if ($prompt->showActivityLog) {
            $activityLog = $prompt->log->map(function ($log) {
                return [
                    $this->dim(date('H:i:s', $log['timestamp'])),
                    match ($log['type']) {
                        'created'  => $this->green('+') . '  ' . $log['item']['name'],
                        'deleted'  => $this->red('-') . '  ' . $log['item']['name'],
                        'modified' => [
                            $this->yellow('▵') . '  ' . $log['item']['name'],
                            str_repeat(' ', 3) . $log['from'] . $this->dim(' → ') . $log['to'],
                        ],
                    },
                    '',
                ];
            })->flatten();

            while ($activityLog->count() < $height) {
                $activityLog->push(str_repeat(' ', $activityLogWidth));
            }

            $visibleActivityLog = $activityLog->slice(-$height);

            $activityLogLines = $this->scrollbar(
                $visibleActivityLog,
                $visibleActivityLog->keys()->first() ?? 0,
                $height,
                $activityLog->count(),
                $activityLogWidth
            );

            $this->minWidth = $activityLogWidth;
            $this->box('Activity Log', $activityLogLines->implode(PHP_EOL));

            $activityLogLines = collect(explode(PHP_EOL, trim($this->output, PHP_EOL)));

            $this->output = '';

            // $activityLogLines = collect([
            //     $this->dim('Activity Log'),
            //     str_repeat(' ', $activityLogWidth),
            // ])->concat($activityLogLines);
        } else {
            $activityLogLines = collect();
        }

        collect([
            $this->dim(' Total: ') . $prompt->total . str_repeat(' ', $fileListWidth - strlen(' Total: ' . $prompt->total)),
            str_repeat(' ', $fileListWidth),
        ])
            ->concat($fileLines)
            ->zip($activityLogLines)
            ->map(fn ($lines) => $lines->implode(' '))
            ->each(fn ($line) => $this->line('  ' . $line));

        $this->hotkey('a', $prompt->showActivityLog ? 'Hide activity log' : 'Show activity log');

        collect($this->hotkeys())->each(fn ($hotkey) => $this->line('  ' . $hotkey));

        return $this;
    }
}
