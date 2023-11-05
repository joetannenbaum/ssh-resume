<?php

namespace ChewieLab;

use Chewie\Themes\Default\KanbanRenderer;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

use function Laravel\Prompts\text;

class Kanban extends Prompt
{
    public array $columns = [
        [
            'title' => 'To Do',
            'items' => [
                [
                    'title'       => 'Make Kanban Board',
                    'description' => 'But in the terminal?',
                ],
                [
                    'title'       => 'Eat Pizza',
                    'description' => '(Whole pie).',
                ],
            ],
        ],
        [
            'title' => 'In Progress',
            'items' => [
                [
                    'title'       => 'Get Milk',
                    'description' => 'From the store (whole).',
                ],
                [
                    'title'       => 'Learn Go',
                    'description' => 'Charm CLI looks dope.',
                ],
                [
                    'title'       => 'Submit Statamic PR',
                    'description' => 'Nocache tag fix.',
                ],
            ],
        ],
        [
            'title' => 'Done',
            'items' => [
                [
                    'title'       => 'Wait Patiently',
                    'description' => 'For the next prompt.',
                ],
            ],
        ],
    ];

    public int $itemIndex = 0;

    public int $columnIndex = 0;

    public function __construct()
    {
        static::$themes['default'][Kanban::class] = KanbanRenderer::class;

        $this->listenForKeys();
    }

    public function value(): bool
    {
        return true;
    }

    protected function listenForKeys(): void
    {
        $this->on('key', function ($key) {
            if ($key[0] === "\e") {
                match ($key) {
                    Key::UP, Key::UP_ARROW => $this->itemIndex = max(0, $this->itemIndex - 1),
                    Key::DOWN, Key::DOWN_ARROW => $this->itemIndex = min(count($this->columns[$this->columnIndex]['items']) - 1, $this->itemIndex + 1),
                    Key::RIGHT, Key::RIGHT_ARROW => $this->nextColumn(),
                    Key::LEFT, Key::LEFT_ARROW => $this->previousColumn(),
                    default => null,
                };

                return;
            }

            // Keys may be buffered.
            foreach (mb_str_split($key) as $key) {
                if ($key === Key::ENTER) {
                    $this->moveCurrentItem();

                    return;
                }

                match ($key) {
                    'q'     => $this->quit(),
                    'n'     => $this->addNewItem(),
                    default => null,
                };
            }
        });
    }

    protected function nextColumn(): void
    {
        $this->columnIndex = min(count($this->columns) - 1, $this->columnIndex + 1);
        $this->itemIndex = 0;
    }

    protected function previousColumn(): void
    {
        $this->columnIndex = max(0, $this->columnIndex - 1);
        $this->itemIndex = 0;
    }

    protected function addNewItem(): void
    {
        $this->clearListeners();
        $this->capturePreviousNewLines();
        $this->resetCursorPosition();
        $this->eraseDown();

        $title = text('Title', 'Title of task');

        $description = text('Description', 'Description of task');

        $this->columns[$this->columnIndex]['items'][] = [
            'title'       => $title,
            'description' => $description,
        ];

        $this->listenForKeys();
        $this->prompt();
    }

    protected function resetCursorPosition(): void
    {
        $lines = count(explode(PHP_EOL, $this->prevFrame)) - 1;

        $this->moveCursor(-999, $lines * -1);
    }

    protected function moveCurrentItem(): void
    {
        $newColumnIndex = $this->columnIndex + 1;

        if ($newColumnIndex >= count($this->columns)) {
            $newColumnIndex = 0;
        }

        $this->columns[$newColumnIndex]['items'][] = $this->columns[$this->columnIndex]['items'][$this->itemIndex];

        unset($this->columns[$this->columnIndex]['items'][$this->itemIndex]);

        $this->columns[$this->columnIndex]['items'] = array_values($this->columns[$this->columnIndex]['items']);

        $this->itemIndex = max(0, $this->itemIndex - 1);
    }

    protected function quit(): void
    {
        static::terminal()->exit();
    }
}
