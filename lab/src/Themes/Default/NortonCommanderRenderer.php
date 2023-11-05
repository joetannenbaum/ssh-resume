<?php

namespace ChewieLab\Themes\Default;

use Chewie\CommanderPanel;
use Chewie\NortonCommander;
use Laravel\Prompts\Output\BufferedConsoleOutput;
use Laravel\Prompts\Themes\Default\Renderer;
use Symfony\Component\Console\Helper\Table as SymfonyTable;
use Symfony\Component\Console\Helper\TableStyle;

class NortonCommanderRenderer extends Renderer
{
    protected int $tableCellWidth = 0;

    /**
     * Render the table.
     */
    public function __invoke(NortonCommander $commander): string
    {
        $height = $commander->terminal()->lines() - 8;
        $width = $commander->terminal()->cols();

        $this->tableCellWidth = (int) (floor($width / 2) / 3) - 4;

        $tableStyle = (new TableStyle)
            ->setHorizontalBorderChars('─')
            ->setVerticalBorderChars('│', '│')
            ->setCellHeaderFormat($this->dim('<fg=default>%s</>'))
            ->setCellRowFormat('<fg=default>%s</>')
            ->setCrossingChars('┼', '', '', '', '┤', '┘</>', '┴', '└', '├', '<fg=gray>┌', '┬', '┐');

        $tables = collect($commander->panels)->map(function (CommanderPanel $panel) use ($tableStyle, $height, $width) {
            $buffered = new BufferedConsoleOutput;

            $cells = $panel->visible($height - 4);

            $cells = collect($cells)->map(
                fn ($col) => collect($col)->map(fn ($cell, $index) => $this->formatCell($cell, $index, $panel))->join(PHP_EOL)
            )->toArray();

            while (count($cells) < 3) {
                $cells[] = str_repeat(' ', $this->tableCellWidth);
            }

            foreach ($cells as $key => $cell) {
                $cells[$key] = str_repeat(
                    ' ',
                    (int) floor(($this->tableCellWidth - mb_strlen('Name')) / 2)
                ) . 'Name' . PHP_EOL . $cell;
            }

            foreach ($cells as $key => $cell) {
                $verticalPadding = $height - 4 - mb_substr_count($cell, PHP_EOL);
                if ($verticalPadding > 0) {
                    $cells[$key] = $cell . str_repeat(PHP_EOL, $verticalPadding);
                }
            }

            $rows = [$cells];

            if ($panel->active) {
                $tableStyle->setHeaderTitleFormat('<fg=black;bg=cyan>%s</>');
            } else {
                $tableStyle->setHeaderTitleFormat('<fg=white;>%s</>');
            }

            (new SymfonyTable($buffered))
                ->setHeaderTitle(' ' . str_replace($_ENV['HOME'], '~', $panel->currentDir) . ' ')
                ->setRows($rows)
                ->setStyle($tableStyle)
                ->render();

            return trim($buffered->content()) . PHP_EOL . $this->getSelectedInfo($panel, $width);
        });

        collect(explode(PHP_EOL, $tables->shift()))->zip(explode(PHP_EOL, $tables->last()))->each(function ($row) {
            $this->line(' ' . $row[0] . $row[1]);
        });

        $hints = [
            ['1', $this->dim('Help')],
            ['2', $this->dim('Menu')],
            ['3', $this->dim('View')],
            ['4', $this->dim('Edit')],
            ['5', $this->dim('Copy')],
            ['6', $this->dim('RenMov')],
            ['7', $this->dim('Mkdir')],
            ['8', $this->dim('Delete')],
            ['9', $this->dim('PullDn')],
            ['10', $this->dim('Quit')],
        ];

        $hints = collect($hints)
            ->map(fn ($line) => $line[0] . ' ' . $line[1])
            ->join('    ');

        $this->newLine();

        $this->line('  ' . $hints);

        return $this;
    }

    protected function formatCell(array $cell, int $index, CommanderPanel $panel)
    {
        $display = $this->cyan($this->getCellDisplay($cell));

        if ($index === $panel->selectedIndex) {
            return $this->inverse($display);
        }

        return $display;
    }

    protected function getCellDisplay(array $cell)
    {
        if ($cell['type'] === 'dir') {
            return str_pad(strtoupper($cell['name']), $this->tableCellWidth, ' ', STR_PAD_RIGHT);
        }

        if (substr($cell['name'], 0, 1) === '.') {
            return str_pad($cell['name'], $this->tableCellWidth, ' ', STR_PAD_RIGHT);
        }

        $filename = $this->truncate($cell['filename'], $this->tableCellWidth - mb_strlen($cell['extension']) - 1);

        $buffer = $this->tableCellWidth - mb_strlen($cell['extension']) - mb_strlen($filename);

        return $filename . str_repeat(' ', $buffer > 0 ? $buffer : 0) . $cell['extension'];
    }

    protected function getSelectedInfo(CommanderPanel $panel, $width)
    {
        $file = $panel->files[$panel->selectedIndex];

        if ($file['name'] === '..') {
            return '';
        }

        if ($file['type'] === 'dir') {
            $description = '▶︎ SUB-DIR ◀︎    ';
            $widthBuffer = 4;
        } else {
            $description = '';
            $widthBuffer = 6;
        }

        $rightAligned = $description . $file['last_modified'];

        $tableWidth = (int) floor(($width / 2)) - $widthBuffer;

        $buffer = $tableWidth - mb_strwidth($rightAligned) - mb_strwidth($file['name']);

        $tableStyle = (new TableStyle)
            ->setHorizontalBorderChars('─')
            ->setVerticalBorderChars('│', '│')
            ->setCellHeaderFormat($this->dim('<fg=default>%s</>'))
            ->setCellRowFormat('<fg=default>%s</>')
            ->setCrossingChars('┼', '', '', '', '┤', '┘</>', '┴', '└', '├', '<fg=gray>┌', '┬', '┐');

        $buffered = new BufferedConsoleOutput;

        (new SymfonyTable($buffered))
            ->setRows([
                [
                    $this->cyan(strtoupper($file['name']) . str_repeat(' ', $buffer > 0 ? $buffer : 0) . $rightAligned),
                ],
            ])
            ->setStyle($tableStyle)
            ->render();

        return trim($buffered->content());
    }
}
