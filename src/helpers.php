<?php

namespace Chewie;

use Chewie\Components\Column;
use Chewie\Components\Content;
use Chewie\Components\Hint;
use Chewie\Components\Hints;
use Chewie\Components\Link;
use Chewie\Components\Nav;
use Chewie\Components\Row;
use Laravel\Prompts\Prompt;

function layout(...$children): string
{
    $components = array_map(
        fn ($child) => $child(
            Prompt::terminal()->cols(),
            // TODO: How do we determine the height of the terminal that we need...?
            // Taking into account other elements on the screen? Hints? Other boxes?
            Prompt::terminal()->lines() - 2,
        ),
        $children
    );

    return implode("\n", $components);
}

function row(...$children): callable
{
    return function ($width, $height) use ($children) {
        return new Row($width, $height, $children);
    };
}

function column(int $colWidth, callable $content): callable
{
    return function ($width, $height) use ($colWidth, $content) {
        return new Column($width, $height, $colWidth, $content);
    };
}

function nav(...$children): callable
{
    return function ($width, $height) use ($children) {
        return new Nav($width, $height, $children);
    };
}

function link(string $label, bool $active = false): callable
{
    return function ($width, $height) use ($label, $active) {
        return new Link($width, $height, $label, $active);
    };
}

function content(string $content): callable
{
    return function ($width, $height) use ($content) {
        return new Content($width, $height, $content);
    };
}

function hints(...$children): callable
{
    return function ($width, $height) use ($children) {
        return new Hints($width, $height, $children);
    };
}

function hint(string $key, string $description, bool $enabled = true): string
{
    return new Hint($key, $description, $enabled);
}
