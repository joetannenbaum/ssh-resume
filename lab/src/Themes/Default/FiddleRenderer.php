<?php

namespace ChewieLab\Themes\Default;

use Chewie\Fiddle;
use Laravel\Prompts\Themes\Default\Renderer;

use function Chewie\column;
use function Chewie\content;
use function Chewie\hint;
use function Chewie\hints;
use function Chewie\layout;
use function Chewie\link;
use function Chewie\nav;
use function Chewie\row;

class FiddleRenderer extends Renderer
{
    public function __invoke(Fiddle $fiddle)
    {
        return layout(
            row(
                column(25, nav(
                    ...collect($fiddle->nav)
                        ->map(fn ($label, $index) => link(
                            $label,
                            $index === $fiddle->page
                        )),
                )),
                column(75, content(
                    $fiddle->state === 'detail'
                        ? $fiddle->pageContent[$fiddle->page]
                        : ''
                )),
            ),
            hints(
                hint('↑ ↓', 'Navigate', $fiddle->state !== 'detail'),
                hint('↩️', 'Select', $fiddle->state !== 'detail'),
                hint('←', 'Back to Nav', $fiddle->state === 'detail'),
                hint('q', 'Quit'),
            ),
        );
    }
}
