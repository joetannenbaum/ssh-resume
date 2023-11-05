<?php

namespace ChewieLab\Themes\Default;

use ChewieLab\Graph;
use Laravel\Prompts\Themes\Default\Renderer;

class GraphRenderer extends Renderer
{
    protected int $tableCellWidth = 0;

    /**
     * Render the table.
     */
    public function __invoke(Graph $graph): string
    {
        $this->newLine(15);

        $height = $graph->terminal()->lines() - 10;

        $columns = collect();

        $firstColumn = collect();
        $i = 0;

        while ($i <= floor($height / 2 / 2)) {
            $firstColumn->prepend(str_pad(str_pad($i + 1, 2, ' ', STR_PAD_LEFT), 3));
            $firstColumn->prepend($this->dim('   '));
            $i++;
        }

        $columns->push($firstColumn);

        foreach ($graph->numbers as $index => $number) {
            $column = collect();
            $mode = 'dash';

            $color = $graph->colors[$index];

            foreach (range(0, $number) as $i => $n) {
                if ($i === 0) {
                    $char = '▀';
                } elseif ($i === $number) {
                    $char = '▄';
                } else {
                    $char = '█';
                }

                $column->prepend($this->dim(str_repeat($mode === 'space' ? ' ' : '─', 5)) . $graph->{$color}(str_repeat($char, 6)));
                $mode = $mode === 'dash' ? 'space' : 'dash';
            }

            while ($column->count() <= floor($height / 2)) {
                $column->prepend($this->dim(str_repeat($mode === 'space' ? ' ' : '─', 11)));
                $mode = $mode === 'dash' ? 'space' : 'dash';
            }

            $columns->push($column);
        }

        $lastColumn = collect();
        $i = 0;

        while ($i <= floor($height / 2 / 2)) {
            $lastColumn->prepend($this->dim(str_repeat('─', 5)));
            $lastColumn->prepend($this->dim(str_repeat(' ', 5)));
            $i++;
        }

        $columns->push($lastColumn);

        collect($columns->shift())->zip(...$columns)->map(fn ($lines) => $lines->implode(''))->each(fn ($line) => $this->line($line));

        return $this;
    }
}
