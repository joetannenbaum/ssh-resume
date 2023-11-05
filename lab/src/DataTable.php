<?php

namespace ChewieLab;

use Chewie\Concerns\CreatesAnAltScreen;
use Chewie\Themes\Default\DataTableRenderer;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;

class DataTable extends Prompt
{
    use CreatesAnAltScreen;
    use RegistersThemes;
    use TypedValue;

    public array $headers;

    public array $rows;

    public int $perPage = 10;

    public int $page = 1;

    public int $index = 0;

    public string $query = '';

    public int $totalPages;

    public string $jumpToPage = '';

    public function __construct(array|Collection $headers = [], array|Collection $rows = [])
    {
        $this->registerTheme(DataTableRenderer::class);

        $this->headers = $headers instanceof Collection ? $headers->all() : $headers;
        $this->rows = $rows instanceof Collection ? $rows->all() : $rows;

        $this->totalPages = (int) ceil(count($this->rows) / $this->perPage);

        $this->listenForHotkeys();

        // $this->createAltScreen();
    }

    public function __destruct()
    {
        // $this->exitAltScreen();
    }

    public function visible(): array
    {
        if ($this->query !== '') {
            $filtered = array_filter($this->rows, function ($row) {
                return str_contains(mb_strtolower(implode(' ', $row)), mb_strtolower($this->query));
            });

            $this->totalPages = (int) ceil(count($filtered) / $this->perPage);

            return array_slice($filtered, 0, $this->perPage);
        }

        $this->totalPages = (int) ceil(count($this->rows) / $this->perPage);

        return array_slice($this->rows, ($this->page - 1) * $this->perPage, $this->perPage);
    }

    public function value(): array
    {
        return $this->visible()[$this->index];
    }

    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->query === '') {
            return $this->dim($this->addCursor('', 0, $maxWidth));
        }

        return $this->addCursor($this->query, $this->cursorPosition, $maxWidth);
    }

    public function jumpValueWithCursor(int $maxWidth): string
    {
        if ($this->jumpToPage === '') {
            return $this->dim($this->addCursor('', 0, $maxWidth));
        }

        return $this->addCursor($this->jumpToPage, $this->cursorPosition, $maxWidth);
    }

    protected function quit(): void
    {
        $this->state = 'cancel';
        exit;
    }

    protected function listenForHotkeys(): void
    {
        KeyPressListener::for($this)
            ->on(
                [Key::UP, Key::UP_ARROW],
                fn () => $this->index = max(0, $this->index - 1),
            )
            ->on(
                [Key::DOWN, Key::DOWN_ARROW],
                fn () => $this->index = min($this->perPage - 1, $this->index + 1),
            )
            ->on(
                [Key::RIGHT, Key::RIGHT_ARROW],
                function () {
                    $this->page = min($this->totalPages, $this->page + 1);
                    $this->index = 0;
                },
            )
            ->on(
                [Key::LEFT, Key::LEFT_ARROW],
                function () {
                    $this->page = max(1, $this->page - 1);
                    $this->index = 0;
                },
            )
            ->on(Key::ENTER, $this->submit(...))
            ->on('q', $this->quit(...))
            ->on('/', $this->search(...))
            ->on('j', $this->jump(...))
            ->listen();
    }

    protected function search(): void
    {
        $this->state = 'search';
        $this->index = 0;
        $this->page = 1;

        KeyPressListener::for($this)
            ->clearExisting()
            ->listenToInput($this->query, $this->cursorPosition)
            ->on(
                Key::ENTER,
                function () {
                    if (count($this->visible()) === 0) {
                        return;
                    }

                    $this->clearListeners();
                    $this->state = 'select';
                    $this->listenForHotkeys();
                },
            )
            ->listen();
    }

    protected function jump(): void
    {
        $this->state = 'jump';
        $this->index = 0;

        KeyPressListener::for($this)
            ->clearExisting()
            ->listenToInput($this->jumpToPage, $this->cursorPosition)
            ->on(
                Key::ENTER,
                function () {
                    if ($this->jumpToPage === '') {
                        $this->clearListeners();
                        $this->state = 'select';
                        $this->listenForHotkeys();

                        return;
                    }

                    if (!is_numeric($this->jumpToPage)) {
                        return;
                    }

                    if ($this->jumpToPage < 1 || $this->jumpToPage > $this->totalPages) {
                        return;
                    }

                    $this->page = $this->jumpToPage;
                    $this->jumpToPage = '';
                    $this->clearListeners();
                    $this->state = 'select';
                    $this->listenForHotkeys();
                },
            )
            ->listen();
    }
}
