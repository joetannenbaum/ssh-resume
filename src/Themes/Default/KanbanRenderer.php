<?php

namespace Chewie\Themes\Default;

use Chewie\Kanban;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Renderer;

class KanbanRenderer extends Renderer
{
    use DrawsBoxes;

    public function __invoke(Kanban $kanban): string
    {
        // Available width of terminal minus some buffer
        $totalWidth = $kanban->terminal()->cols() - 16;
        // Available height of terminal minus some buffer
        $totalHeight = $kanban->terminal()->lines() - 7;

        // Column width should be the total width divided by the number of columns
        $columnWidth = (int) floor($totalWidth / count($kanban->columns));
        // Column width minus some padding
        $cardWidth = $columnWidth - 6;

        // Loop through our columns and render each one
        $columns = collect($kanban->columns)->map(function ($column, $columnIndex) use (
            $cardWidth,
            $columnWidth,
            $kanban,
            $totalHeight,
        ) {
            // Loop through each card in the column and render it
            $cardsOutput = collect($column['items'])->map(
                fn ($card, $cardIndex) => $this->getBoxOutput(
                    $card['title'],
                    PHP_EOL . $card['description'] . PHP_EOL,
                    $cardIndex === $kanban->itemIndex && $kanban->columnIndex === $columnIndex ? 'green' : 'dim',
                    $cardWidth,
                ),
            );

            $cardContent = PHP_EOL . $cardsOutput->implode(PHP_EOL);

            // Add new lines to the card content to make it the same height as the terminal
            $cardContent .= str_repeat(PHP_EOL, $totalHeight - count(explode(PHP_EOL, $cardContent)) + 1);

            $columnTitle = $kanban->columns[$columnIndex]['title'];

            $columnContent = $this->getBoxOutput(
                $kanban->columnIndex === $columnIndex ? $this->cyan($columnTitle) : $this->dim($columnTitle),
                $cardContent,
                $kanban->columnIndex === $columnIndex ? 'cyan' : 'dim',
                $columnWidth,
            );

            // Render the column with a dimmed border and the card content
            return explode(PHP_EOL, $columnContent);
        });

        // Zip the columns together so we can render them side by side
        collect($columns->shift())
            ->zip(...$columns)
            ->map(fn ($lines) => $lines->implode(''))
            // Render the lines
            ->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function getBoxOutput(string $title, string $body, string $color, int $width): string
    {
        // Reset the output string
        $this->output = '';

        // Set the minWidth to the desired width, the box method
        // uses this to calculate how wide the box should be
        $this->minWidth = $width;

        $this->box(
            title: $title,
            body: $body,
            color: $color,
        );

        $content = $this->output;

        $this->output = '';

        return $content;
    }
}
