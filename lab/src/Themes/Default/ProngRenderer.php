<?php

namespace ChewieLab\Themes\Default;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Output\Lines;
use ChewieLab\Prong;
use ChewieLab\Prong\Ball;
use ChewieLab\Prong\Title;
use Laravel\Prompts\Themes\Default\Renderer;

class ProngRenderer extends Renderer
{
    use Aligns;
    use DrawsHotkeys;

    public function __invoke(Prong $prompt): string
    {
        if ($prompt->state === 'title') {
            return $this->titleScreen($prompt);
        }

        if ($prompt->winner !== null) {
            return $this->winnerScreen($prompt);
        }

        return $this->playGame($prompt);
    }

    protected function playGame(Prong $prompt): static
    {
        /** @var Ball $ball */
        $ball = $prompt->loopable(Ball::class);

        /** @var Paddle $player1 */
        $player1 = $prompt->loopable('player1');

        /** @var Paddle $player2 */
        $player2 = $prompt->loopable('player2');

        $paddle1 = $this->paddle($prompt, $player1->value->current(), 'red');
        $paddle2 = $this->paddle($prompt, $player2->value->current(), 'green');
        $ball = $this->ball($prompt, $ball);

        $cols = collect([$paddle1, $ball, $paddle2])->map(fn ($el) => explode(PHP_EOL, $el));

        Lines::fromColumns($cols)->alignNone()->lines()->each(fn ($line) => $this->line($line));

        $this->hotkey('w', 'Move up');
        $this->hotkey('s', 'Move down');

        $player1Hotkeys = $this->hotkeys();

        $this->clearHotkeys();

        $this->hotkey('i', 'Move up');
        $this->hotkey('k', 'Move down');

        $player2Hotkeys = $this->hotkeys();

        $this->line($this->spaceBetween($prompt->width, $this->bold('Player 1'), $this->bold('Player 2')));
        $this->line($this->spaceBetween($prompt->width, $player1Hotkeys[0], $player2Hotkeys[0]));

        return $this;
    }

    protected function winnerScreen(Prong $prompt): static
    {
        $title = $prompt->winner === 1 ? $this->player1Won() : $this->player2Won();
        $title = collect(explode(PHP_EOL, $title));

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('q')) . ' to quit or ' . $this->bold($this->cyan('r')) . ' to restart');

        $this->center($title, $prompt->width, $prompt->height)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function titleScreen(Prong $prompt): static
    {
        $title = collect(explode(PHP_EOL, $this->title()));

        $title->push('');
        $title->push('Press ' . $this->bold($this->cyan('any key')) . ' to start');

        $title = $title->map(fn ($line, $index) => $index > $prompt->loopable(Title::class)->value->current() ? '' : $line);

        $this->center($title, $prompt->width, $prompt->height)->each(fn ($line) => $this->line($line));

        return $this;
    }

    protected function ball(Prong $prong, Ball $ball): string
    {
        $emptyLine = str_repeat(' ', $prong->width) . PHP_EOL;

        // Pad the top
        $output = str_repeat($emptyLine, $ball->y);

        // Draw the ball
        $output .= str_repeat(' ', $ball->x)
            . $this->cyan('◼️')
            . str_repeat(' ', max($prong->width - $ball->x - 1, 0))
            . PHP_EOL;

        $bottomPadding = $prong->height - $ball->y;

        if ($bottomPadding > 0) {
            $output .= str_repeat($emptyLine, $bottomPadding);
        }

        return $output;
    }

    protected function paddle(Prong $prong, $y, $color): string
    {
        $paddleHeight = 5;
        $output = str_repeat(' ' . PHP_EOL, $y);

        $output .= str_repeat($this->{$color}('█') . PHP_EOL, $paddleHeight) . ' ' . PHP_EOL;

        $extraLines = $prong->height - $y - $paddleHeight;

        if ($extraLines > 0) {
            $output .= str_repeat(' ' . PHP_EOL, $extraLines);
        }

        return $output;
    }

    protected function player1Won()
    {
        return <<<TEXT
         ____  _       ____  __ __    ___  ____        ___   ____     ___      __    __   ___   ____   __
         |    \| |     /    ||  |  |  /  _]|    \      /   \ |    \   /  _]    |  |__|  | /   \ |    \ |  |
         |  o  ) |    |  o  ||  |  | /  [_ |  D  )    |     ||  _  | /  [_     |  |  |  ||     ||  _  ||  |
         |   _/| |___ |     ||  ~  ||    _]|    /     |  O  ||  |  ||    _]    |  |  |  ||  O  ||  |  ||__|
        |  |  |     ||  _  ||___, ||   [_ |    \     |     ||  |  ||   [_     |  `  '  ||     ||  |  | __
         |  |  |     ||  |  ||     ||     ||  .  \    |     ||  |  ||     |     \      / |     ||  |  ||  |
         |__|  |_____||__|__||____/ |_____||__|\_|     \___/ |__|__||_____|      \_/\_/   \___/ |__|__||__|
        TEXT;
    }

    protected function player2Won()
    {
        return <<<TEXT
         ____  _       ____  __ __    ___  ____       ______  __    __   ___       __    __   ___   ____   __
         |    \| |     /    ||  |  |  /  _]|    \     |      ||  |__|  | /   \     |  |__|  | /   \ |    \ |  |
         |  o  ) |    |  o  ||  |  | /  [_ |  D  )    |      ||  |  |  ||     |    |  |  |  ||     ||  _  ||  |
         |   _/| |___ |     ||  ~  ||    _]|    /     |_|  |_||  |  |  ||  O  |    |  |  |  ||  O  ||  |  ||__|
        |  |  |     ||  _  ||___, ||   [_ |    \       |  |  |  `  '  ||     |    |  `  '  ||     ||  |  | __
         |  |  |     ||  |  ||     ||     ||  .  \      |  |   \      / |     |     \      / |     ||  |  ||  |
         |__|  |_____||__|__||____/ |_____||__|\_|      |__|    \_/\_/   \___/       \_/\_/   \___/ |__|__||__|
        TEXT;
    }

    protected function title()
    {
        return <<<TEXT
         ____  ____   ___   ____    ____
        |    \|    \ /   \ |    \  /    |
        |  o  )  D  )     ||  _  ||   __|
        |   _/|    /|  O  ||  |  ||  |  |
        |  |  |    \|     ||  |  ||  |_ |
        |  |  |  .  \     ||  |  ||     |
        |__|  |__|\_|\___/ |__|__||___,_|
        TEXT;
    }
}
