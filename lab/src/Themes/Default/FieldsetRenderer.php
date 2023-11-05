<?php

namespace ChewieLab\Themes\Default;

use Chewie\Fieldset;
use Laravel\Prompts\Themes\Default\Renderer;

class FieldsetRenderer extends Renderer
{
    protected int $tableCellWidth = 0;

    /**
     * Render the table.
     */
    public function __invoke(Fieldset $set): string
    {
        return $this;
    }
}
