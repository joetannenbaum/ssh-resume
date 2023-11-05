<?php

namespace ChewieLab\Themes\Default;

use Chewie\Chat;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class ChatRenderer extends Renderer
{
    use DrawsBoxes;
    use DrawsScrollbars;

    protected int $height = 0;

    protected int $sidebarWidth = 25;

    public function __invoke(Chat $chat)
    {
        $this->height = $chat->terminal()->lines() - 2;

        $sidebar = $this->sidebar($chat);
        $chatWindow = $this->chatWindow($chat);

        collect(explode(PHP_EOL, $sidebar))->zip(explode(PHP_EOL, $chatWindow))->each(function ($lines) {
            [$sidebar, $chatWindow] = $lines;

            $this->line($sidebar . ' ' . $chatWindow);
        });

        return $this;
    }

    public function sidebar(Chat $chat)
    {
        $header = ' Hi, ' . $this->bold($this->cyan($chat->user['name']));

        while (strlen($this->stripEscapeSequences($header)) < $this->sidebarWidth) {
            $header .= ' ';
        }

        $output = collect([$header, $this->dim(str_repeat('─', $this->sidebarWidth))]);

        foreach ($chat->users as $index => $user) {
            if ($user['id'] === $chat->user['id']) {
                continue;
            }

            $formatted = str_pad(' ' . $this->{$user['color']}('◾') . $user['name'], $this->sidebarWidth + 11, ' ', STR_PAD_RIGHT);

            if ($index === $chat->activeUserIndex) {
                $user = $this->bgCyan($this->bold($formatted));
            } elseif ($index === $chat->selectedUserIndex) {
                $user = $this->bgBlack($formatted);
            } else {
                $user = $formatted;
            }

            if ($chat->state !== 'users') {
                $user = $this->dim($user);
            }

            $output->push($user);
        }

        if (count($chat->users) === 0) {
            $output->push($this->dim(str_pad('Lonely in here...', $this->sidebarWidth, ' ', STR_PAD_RIGHT)));
        }

        while ($output->count() < $this->height) {
            $output->push(str_repeat(' ', $this->sidebarWidth));
        }

        return $output->map(fn ($l) => $l . $this->dim('│'))->implode(PHP_EOL);
    }

    public function chatWindow(Chat $chat)
    {
        $maxWidth = $chat->terminal()->cols() - $this->sidebarWidth - 10;

        $this->minWidth = $maxWidth;

        $height = $this->height - 5;

        if (count($chat->users) === 0) {
            return '';
        }

        $currentChatId = collect([$chat->user['id'], $chat->users[$chat->activeUserIndex]['id']])->sort()->implode('-');

        $messagesFormatted = collect($chat->chatMessages[$currentChatId] ?? [])->map(function ($message) use ($chat) {
            $user = collect($chat->users)->firstWhere('id', $message['user']) ?? $chat->user;

            return [
                $this->{$user['color']}($user['name']) . ' ' . $this->dim(date('H:i:s', $message['timestamp'])),
                $message['message'],
                ' ',
            ];
        })->flatten();

        if ($chat->state !== 'chat') {
            $messagesFormatted = $messagesFormatted->map(fn ($line) => $this->dim($line));
        }

        $visible = array_slice($messagesFormatted->toArray(), -$height);

        $output = $this->scrollbar(
            visible: collect($visible)->map(fn ($line) => '  ' . $line),
            firstVisible: max(abs(count($visible) - count($messagesFormatted)), 0),
            height: $height,
            total: $messagesFormatted->count(),
            width: $maxWidth + 4,
        );

        while ($output->count() < $height) {
            $output->push('');
        }

        $output->push('');

        $this->box('', $chat->valueWithCursor($maxWidth));

        $output->push($this->output);

        $this->output = '';

        return $output->implode(PHP_EOL);
    }
}
