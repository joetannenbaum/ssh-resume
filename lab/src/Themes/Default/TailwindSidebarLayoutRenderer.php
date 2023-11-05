<?php

namespace ChewieLab\Themes\Default;

use Chewie\Components\Column;
use Chewie\TailwindSidebarLayout;
use Laravel\Prompts\Themes\Default\Renderer;

class TailwindSidebarLayoutRenderer extends Renderer
{
    protected TailwindSidebarLayout $tw;

    public function __invoke(TailwindSidebarLayout $tw)
    {
        $this->tw = $tw;

        $sidebar = (new Column($tw, 25))->borderRight()->render(function (Column $col) {
            $active = $this->tw->sidebar[$this->tw->sidebarIndex];

            $col->navLink('Dashboard', $active === 'dashboard');
            $col->navLink('Team', $active === 'team');
            $col->navLink('Projects', $active === 'projects');
            $col->navLink('Calendar', $active === 'calendar');
            $col->navLink('Documents', $active === 'documents');
            $col->navLink('Reports', $active === 'reports');
        });

        $mainPanel = (new Column($tw, 75))->render(function (Column $col) {
            match ($this->tw->state) {
                'dashboard' => $col->content($this->bold('Dashboard')),
                'team'      => $col->content($this->bold('Team')),
                'projects'  => $col->content($this->bold('Projects')),
                'calendar'  => $col->content($this->bold('Calendar')),
                'documents' => $col->content($this->bold('Documents')),
                'reports'   => $col->content($this->bold('Reports')),
                default     => null,
            };
        });

        $sidebarLines = collect(explode(PHP_EOL, $sidebar));
        $mainPanelLines = collect(explode(PHP_EOL, $mainPanel));

        collect($sidebarLines)->zip($mainPanelLines)->each(function ($lines) {
            [$sidebarLine, $mainPanelLine] = $lines;

            $this->line($sidebarLine . $mainPanelLine);
        });

        return $this;
    }
}
