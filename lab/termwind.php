<?php

use Laravel\Prompts\Output\BufferedConsoleOutput;
use Termwind\Termwind;

require __DIR__ . '/../vendor/autoload.php';

use function Termwind\{render};
use function Termwind\{style};

$output = new BufferedConsoleOutput;

Termwind::renderUsing($output);

style('green-300')->color('#bada55');
style('btn')->apply('p-4 bg-green-300 text-white');

render('<div class="btn">Click me</div>');

// single line html...
render('<div class="px-1 bg-green-300">Termwind</div>');

// multi-line html...
render(<<<'HTML'
    <div>
        <div class="px-1 bg-green-600">Termwind</div>
        <em class="ml-1">
          Give your CLI apps a unique look
        </em>
    </div>
HTML);

render(<<<'HTML'
    <div class="bg-blue-500 sm:bg-red-600">
        If bg is blue is sm, if red > than sm breakpoint.
    </div>
HTML);

render(<<<'HTML'
    <div>This is a div element.</div>
HTML);

render(<<<'HTML'
    <p>This is a paragraph.</p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI app built with <span class="text-green-300">Termwind</span>.
    </p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI app built with Termwind. <a href="/">Click here to open</a>
    </p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI app built with <b>Termwind</b>.
    </p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI app built with <i>Termwind</i>.
    </p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI app built with <s>Termwind</s>.
    </p>
HTML);

render(<<<'HTML'
    <p>
        This is a CLI <br>
        app built with Termwind.
    </p>
HTML);

render(<<<'HTML'
    <ul>
        <li>Item 1</li>
        <li>Item 2</li>
    </ul>
HTML);

render(<<<'HTML'
    <ol>
        <li>Item 1</li>
        <li>Item 2</li>
    </ol>
HTML);

render(<<<'HTML'
    <ul>
        <li>Item 1</li>
    </ul>
HTML);

render(<<<'HTML'
    <dl>
        <dt>🍃 Termwind</dt>
        <dd>Give your CLI apps a unique look</dd>
    </dl>
HTML);

render(<<<'HTML'
    <div>
        <div>🍃 Termwind</div>
        <hr>
        <p>Give your CLI apps a unique look</p>
    </div>
HTML);

render(<<<'HTML'
    <table>
        <thead>
            <tr>
                <th>Task</th>
                <th>Status</th>
            </tr>
        </thead>
        <tr>
            <th>Termwind</th>
            <td>✓ Done</td>
        </tr>
    </table>
HTML);

render(<<<'HTML'
    <pre>
        Text in a pre element
        it preserves
        both      spaces and
        line breaks
    </pre>
HTML);

render(<<<'HTML'
    <code line="22" start-line="20">
        try {
            throw new \Exception('Something went wrong');
        } catch (\Throwable $e) {
            report($e);
        }
    </code>
HTML);

dd($output);
