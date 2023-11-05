<?php

namespace Chewie\Output;

use const PHP_EOL;

use Laravel\Prompts\Output\ConsoleOutput;
use React\Stream\WritableResourceStream;

class NonBlockingConsoleOutput extends ConsoleOutput
{
    protected WritableResourceStream $nonBlockingStream;

    /**
     * Write the output and capture the number of trailing new lines.
     */
    protected function doWrite(string $message, bool $newline): void
    {
        if ($newline) {
            $message .= PHP_EOL;
        }

        $this->nonBlockingStream ??= new WritableResourceStream(STDOUT);

        $this->nonBlockingStream->write($message);

        $trailingNewLines = strlen($message) - strlen(rtrim($message, PHP_EOL));

        if (trim($message) === '') {
            $this->newLinesWritten += $trailingNewLines;
        } else {
            $this->newLinesWritten = $trailingNewLines;
        }
    }
}
