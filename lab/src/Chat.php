<?php

namespace ChewieLab;

use Chewie\Output\NonBlockingConsoleOutput;
use ChewieLab\Themes\Default\ChatRenderer;
use Laravel\Prompts\Concerns\TypedValue;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use React\EventLoop\Loop;
use React\Promise\Promise;
use React\Stream\ReadableResourceStream;
use Throwable;

use function Laravel\Prompts\text;
use function React\Promise\all;

class Chat extends Prompt
{
    use TypedValue;

    public int $activeUserIndex = 0;

    public int $selectedUserIndex = 0;

    public array $chatMessages = [];

    public string $currentMessage = '';

    public array $users = [];

    public array $chats = [];

    public int $firstVisibleMessage = 0;

    public bool $initialRender = true;

    public array $user;

    protected string $lastMessageContent = '';

    protected string $lastUsersContent = '';

    protected string $chatPath = __DIR__ . '/../chat/chats.json';

    protected string $usersPath = __DIR__ . '/../chat/users.json';

    public function __construct()
    {
        static::$themes['default'][Chat::class] = ChatRenderer::class;

        $username = text('What is your name?');

        $this->loadUsers($username);

        foreach ([$this->chatPath, $this->usersPath] as $path) {
            if (!file_exists($path)) {
                file_put_contents($path, json_encode([]));
            }
        }

        if (!isset($this->user)) {
            $colors = ['red', 'green', 'yellow', 'blue', 'magenta'];

            $this->user = [
                'name'  => $username,
                'color' => $colors[count($this->users)],
                'id'    => time(),
            ];

            $this->users[] = $this->user;

            file_put_contents($this->usersPath, json_encode($this->users));
        }

        $this->moveCursor(0, 0);
        $this->eraseDown();

        static::setOutput(new NonBlockingConsoleOutput);

        $this->state = 'chat';

        // tput smcup
        fwrite(STDOUT, "\e[?1049h");
        // static::output()->write("\e[?1049h");
    }

    public function __destruct()
    {
        // tput rmcup
        fwrite(STDOUT, "\e[?1049l");
        // static::output()->write("\e[?1049l");
    }

    public function chat()
    {
        try {
            $this->capturePreviousNewLines();

            if (static::shouldFallback()) {
                return $this->fallback();
            }

            static::$interactive ??= stream_isatty(STDIN);

            if (!static::$interactive) {
                return $this->default();
            }

            try {
                static::terminal()->setTty('-icanon -isig -echo');
            } catch (Throwable $e) {
                static::output()->writeln("<comment>{$e->getMessage()}</comment>");
                static::fallbackWhen(true);

                return $this->fallback();
            }

            $this->hideCursor();

            $stream = new ReadableResourceStream(STDIN);

            $stream->on('data', function ($chunk) {
                $this->handleKey($chunk);
            });
        } finally {
            $this->clearListeners();
        }

        Loop::addPeriodicTimer(.5, function (): void {
            all([
                new Promise(function ($resolve) {
                    $contents = file_get_contents($this->chatPath);

                    if ($contents === $this->lastMessageContent) {
                        return $resolve(false);
                    }

                    $this->lastMessageContent = $contents;

                    $this->chatMessages = json_decode($contents, true);

                    if ($this->initialRender) {
                        // $this->showMostRecentMessage();
                        $this->initialRender = false;
                    }

                    return $resolve(true);
                }),
                new Promise(function ($resolve) {
                    $contents = file_get_contents($this->usersPath);

                    if ($contents === $this->lastUsersContent) {
                        return $resolve(false);
                    }

                    $this->lastUsersContent = $contents;

                    $this->loadUsers();

                    return $resolve(true);
                }),
            ])->then(function ($results) {
                if (in_array(true, $results)) {
                    $this->render();
                }
            });
        });

        $this->render();
    }

    public function value(): mixed
    {
        return $this->typedValue;
    }

    /**
     * Get the entered value with a virtual cursor.
     */
    public function valueWithCursor(int $maxWidth): string
    {
        if ($this->value() === '') {
            return $this->dim($this->addCursor('What do you want to say?', 0, $maxWidth));
        }

        return $this->addCursor($this->value(), $this->cursorPosition, $maxWidth);
    }

    protected function loadUsers($username = null)
    {
        $users = json_decode(file_get_contents($this->usersPath), true);

        if ($username) {
            [$user, $users] = collect($users)->partition(fn ($u) => $u['name'] === $username);

            if ($user->isNotEmpty()) {
                $this->user = $user->first();
            }
        } else {
            [$user, $users] = collect($users)->partition(fn ($u) => $u['name'] === $this->user['name']);
        }

        $this->users = $users->values()->toArray();
    }

    // public function messages($maxWidth)
    // {
    //     $output = collect(explode(PHP_EOL, $this->chatMessages))
    //         ->map(fn ($line) => wordwrap($line, $maxWidth, "\n", true))
    //         ->implode(PHP_EOL);

    //     return collect(explode(PHP_EOL, trim($output)));
    // }

    /**
     * Submit the prompt.
     */
    protected function submit(): void
    {
        if ($this->typedValue === '') {
            return;
        }

        $chat = json_decode(file_get_contents($this->chatPath), true);

        $chatId = collect([$this->user['id'], $this->users[$this->selectedUserIndex]['id']])->sort()->implode('-');

        $chat[$chatId][] = [
            'user'      => $this->user['id'],
            'message'   => $this->typedValue,
            'timestamp' => time(),
        ];

        file_put_contents($this->chatPath, json_encode($chat));

        $this->typedValue = '';
        $this->cursorPosition = 0;
        // $this->showMostRecentMessage();
    }

    protected function showMostRecentMessage()
    {
        // $this->firstVisibleMessage = abs($this->terminal()->lines() - 10 - count($this->messages($this->terminal()->cols() - 35))) + 1;
    }

    protected function handleKey($key)
    {
        if ($key === Key::CTRL_C) {
            static::terminal()->exit();
        }

        if ($key === Key::TAB) {
            $this->state = 'users';
            $this->render();

            return;
        }

        if ($this->state === 'users') {
            if ($key[0] === "\e" || in_array($key, [Key::CTRL_B, Key::CTRL_F, Key::CTRL_A, Key::CTRL_E])) {
                match ($key) {
                    Key::UP, Key::UP_ARROW => $this->selectedUserIndex = max(0, $this->selectedUserIndex - 1),
                    Key::DOWN, Key::DOWN_ARROW => $this->selectedUserIndex = min(count($this->users) - 1, $this->selectedUserIndex + 1),
                    default => null,
                };

                $this->render();

                return;
            }

            if ($key === Key::ENTER) {
                $this->activeUserIndex = $this->selectedUserIndex;
                $this->state = 'chat';

                $this->render();

                return;
            }

            return;
        }

        if ($key[0] === "\e" || in_array($key, [Key::CTRL_B, Key::CTRL_F, Key::CTRL_A, Key::CTRL_E])) {
            match ($key) {
                Key::LEFT, Key::LEFT_ARROW, Key::CTRL_B => $this->cursorPosition = max(0, $this->cursorPosition - 1),
                Key::RIGHT, Key::RIGHT_ARROW, Key::CTRL_F => $this->cursorPosition = min(mb_strlen($this->typedValue), $this->cursorPosition + 1),
                Key::oneOf([Key::HOME, Key::CTRL_A], $key) => $this->cursorPosition = 0,
                Key::oneOf([Key::END, Key::CTRL_E], $key)  => $this->cursorPosition = mb_strlen($this->typedValue),
                Key::DELETE                                => $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition) . mb_substr($this->typedValue, $this->cursorPosition + 1),
                default                                    => null,
            };

            return;
        }

        // Keys may be buffered.
        foreach (mb_str_split($key) as $key) {
            if ($key === Key::ENTER) {
                $this->submit();

                return;
            }

            if ($key === Key::BACKSPACE || $key === Key::CTRL_H) {
                if ($this->cursorPosition === 0) {
                    return;
                }

                $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition - 1) . mb_substr($this->typedValue, $this->cursorPosition);
                $this->cursorPosition--;
            } elseif (ord($key) >= 32) {
                $this->typedValue = mb_substr($this->typedValue, 0, $this->cursorPosition) . $key . mb_substr($this->typedValue, $this->cursorPosition);
                $this->cursorPosition++;
            }
        }

        $this->render();
    }
}
