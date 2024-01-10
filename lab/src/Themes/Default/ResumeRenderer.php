<?php

namespace ChewieLab\Themes\Default;

use Chewie\Concerns\Aligns;
use Chewie\Concerns\DrawsHotkeys;
use Chewie\Concerns\DrawsTables;
use ChewieLab\Resume;
use Laravel\Prompts\Themes\Default\Concerns\DrawsBoxes;
use Laravel\Prompts\Themes\Default\Concerns\DrawsScrollbars;
use Laravel\Prompts\Themes\Default\Renderer;

class ResumeRenderer extends Renderer
{
    use Aligns;
    use DrawsBoxes;
    use DrawsHotkeys;
    use DrawsTables;
    use DrawsScrollbars;

    public function __invoke(Resume $prompt): string
    {
        $idealTextWidth = 80;
        $prompt->height = min($prompt->terminal()->lines() - 10, 40);
        $prompt->width = min($idealTextWidth, $prompt->terminal()->cols() - 6);
        $prompt->maxTextWidth = min($prompt->width - 8, $idealTextWidth);

        $table = $this->table([
            [
                $this->renderContent($prompt),
            ],
        ]);

        $name = file_get_contents(__DIR__ . '/../../../../lab/resume/name.txt');

        collect(explode(PHP_EOL, $name))->map(fn ($line) => $this->line(' ' . $line));

        $this->newLine();

        $links = collect([
            'https://blog.joe.codes',
            'https://github.com/joetannenbaum',
            'https://twitter.com/joetannenbaum',
        ])->map(function ($link) use ($prompt) {
            $termWidth = $prompt->terminal()->cols() - 2;

            if ($termWidth < 92) {
                return str_replace('https://', '', $link);
            }

            return $link;
        })->map(fn ($line) => $this->cyan($line))->implode($this->dim(' · '));

        $this->line(' ' . $links);

        $this->newLine(2);

        $nav = collect($prompt->navigation)->map(
            function ($value, $key) use ($prompt) {
                if ($key === $prompt->page) {
                    return $this->bold($this->underline($this->cyan($value)));
                }

                return $value;
            }
        )->map(fn ($value, $key) => str_repeat(' ', $key === 0 ? 1 : 4) . $value . str_repeat(' ', 4))
            ->values()
            ->implode($this->dim('/'));

        $this->line($nav);

        $table->each(fn ($line) => $this->line($line));

        $this->hotkey('↑ ↓', 'Scroll');
        $this->hotkey('←', 'Previous Section', $prompt->page > 0);
        $this->hotkey('→', 'Next Section', $prompt->page < count($prompt->navigation) - 1);

        $this->hotkey('q', 'Quit');

        $this->newLine();

        collect($this->hotkeys())->each(fn ($line) => $this->line(' ' . $line));

        return $this;
    }

    protected function renderContent(Resume $prompt): string
    {
        $height = $prompt->height - 10;
        $width = $prompt->width;

        $title = $prompt->navigation[$prompt->page];

        $method = 'render' . str_replace(' ', '', $title);

        $lines = collect($this->{$method}($prompt));

        $lines = collect(explode(PHP_EOL, $lines->implode(PHP_EOL)));

        while ($lines->count() < $height) {
            $lines->push('');
        }

        $lines = $lines->map(fn ($line) => $this->pad($line, $width));

        if ($prompt->focused === 'navigation') {
            $lines = $lines->map(fn ($line) => $this->dim($line));
        }

        $scrollPosition = min($prompt->scrollPosition, $lines->count() - $height);

        // TODO: Bad?
        $prompt->scrollPosition = $scrollPosition;

        $visible = $lines->slice($scrollPosition, $height);

        return $this->scrollbar(
            visible: $visible,
            firstVisible: $scrollPosition,
            height: $height,
            total: $lines->count(),
            width: $width,
        )->implode(PHP_EOL);
    }

    protected function renderSummary(Resume $prompt): array
    {
        return [
            $this->wrapped('Seasoned full-stack developer with a focus on building optimized and beautiful apps. I love collaborating with a team, mentoring junior developers, and learning from others.'),
            '',
            $this->wrapped('Excels in building entire systems top-to-bottom: APIs, mobile apps, interactive front-ends, internal tooling, marketing pages. 15+ years in the game and haven\'t gotten bored yet.'),
            '',
            $this->header('Notable Projects'),
            '',
            $this->project('Bellows', 'https://bellows.dev', 'Bellows is an intelligent CLI app built with Laravel Zero that supercharges development workflow from project kickoff to deploying fully configured sites to Laravel Forge.'),
            '',
            $this->project('Blip', 'https://ipblip.com', 'Built with Laravel + Inertia.js, Blip is a full IP-whitelisting toolkit including self-destructing firewalls, IP syncing, and scheduling. Integrates directly with DigitalOcean and AWS.'),
        ];
    }

    protected function renderLinks(Resume $prompt): array
    {
        return collect([
            'https://joe.codes',
            'https://blog.joe.codes',
            'https://github.com/joetannenbaum',
            'https://twitter.com/joetannenbaum',
            'https://www.linkedin.com/in/joe-tannenbaum-27724221',
        ])->map(fn ($link, $i) => ($i === 0 ? '' : PHP_EOL) . $this->cyan($link))->toArray();
    }

    protected function renderExperience(Resume $prompt): array
    {
        return [
            $this->job(
                'Senior Software Engineer',
                'Digital Extremes',
                'August 2023 - Present',
                [
                    'Full-stack software engineer focused on:',
                    '',
                    $this->list(
                        'Improving site performance + resiliancy under heavy traffic',
                        'Implementing and reviewing new features and bugfixes',
                        'Mentoring junior developers',
                    ),
                    '',
                    'Impact:',
                    '',
                    $this->list(
                        'Designed and implemented a custom localization import/export system',
                        'Created a bespoke API resiliancy system',
                        'Built a feature flag system + dashboard based on business needs',
                    ),
                ],
                'first',
            ),

            $this->job(
                'Founder & Lead Software Engineer',
                'Joseph Tannenbaum LLC',
                'September 2017 - August 2023',
                [
                    'Featured Clients:',
                    '',
                    $this->underline('Broadway Brands'),
                    $this->dim('https://broadwaynews.com'),
                    $this->dim('https://broadwaybusiness.com/grosses'),
                    '',
                    $this->list(
                        'Tech Lead for 12+ properties',
                        'Built APIs (Laravel), mobile apps (React Native), SPAs (Laravel + Vue)',
                        'Integral to company acquisition in 2022',
                    ),
                    '',
                    $this->underline('Sift'),
                    $this->dim('https://simplysift.com'),
                    '',
                    $this->list(
                        'Built mobile app (React Native) including live chat, recipe sharing, and recipe importing',
                        'Contributed to API (Laravel)',
                    ),
                    '',
                    $this->underline('Lawline'),
                    $this->dim('https://lawline.com'),
                    '',
                    $this->list(
                        'Built mobile app (React Native) including offline course viewing, certificate redemption, and credit tracking',
                        'Contributed to API (Laravel)',
                    ),
                    '',
                    $this->underline('Built for the Stage'),
                    $this->dim('https://builtforthestage.com'),
                    '',
                    $this->list(
                        'Built custom Statamic site',
                        'Fully customized Stripe integration',
                        'Automated all admin processes',
                    ),
                ],
            ),

            $this->job(
                'Co-Founder & Lead Software Engineer',
                'Sammich Shop',
                'June 2016 - September 2017',
                [
                    $this->wrapped('Development shop focused on building high quality, performant web and mobile applications. Responsibilities included:'),
                    '',
                    $this->list(
                        'Web application architecture and development',
                        'API design and implementation',
                        'Building and maintaining mobile apps for iOS and Android',
                        'Server provisioning + database schema design and optimization',
                    ),
                ],
            ),

            $this->job(
                'Lead Software Engineer',
                'FurtherEd',
                'December 2011 - May 2016',
                [
                    $this->wrapped('Led a team of talented developers that planned and executed a re-build of a framework-less codebase with thousands of active users and millions of rows of data in a highly regulated industry.'),
                    '',
                    $this->list(
                        'Migrated entire system to Laravel',
                        'Designed new normalized database schema and migrated all data',
                        'Split code between a centralized API and multiple front-end applications',
                    ),
                    '',
                    'Impact:',
                    '',
                    $this->list(
                        'Stabilized application (better performance, fewer bugs, handled bursts of traffic)',
                        'Sped up development cycle',
                        'Established best practices moving forward',
                        'Set business up for success for the launch of their mobile app',
                        'Integral to 25% increase of revenue within the first year post-launch ',
                    ),
                ],
            ),

            $this->job(
                'Software Engineer',
                'The Conference Board',
                'June 2009 - December 2011',
                [
                    $this->list(
                        'Participated in the restructuring/redesign of their website',
                        'Built internal tooling to streamline business processes and communication',
                        'Built custom CMS with roles to make website content editable by business entities',
                    ),
                ],
            ),

            $this->job(
                'Software Engineer',
                'Linden Travel (FROSCH)',
                'January 2009 - April 2009',
                [
                    $this->list(
                        'Re-built website using WordPress and a fully custom theme',
                        'Empowered business entities to add and edit website content themselves',
                    ),
                ],
                'last',
            ),
        ];
    }

    protected function list(...$items): string
    {
        $bullet = ' · ';

        return collect($items)
            ->map(fn ($item) => $this->wrapped($bullet . $item))
            ->map(fn ($item) => collect(explode(PHP_EOL, $item))->map(fn ($line, $i) => $i === 0 ? $line : str_repeat(' ', mb_strwidth($bullet)) . $line)->implode(PHP_EOL))
            ->map(fn ($item) => str_replace($bullet, $this->dim($bullet), $item))
            ->implode(PHP_EOL);
    }

    protected function job($title, $company, $duration, $description, $position = null): string
    {
        return collect([
            $this->cyan(match ($position) {
                'first' => '┌─',
                default => '├─',
            }) . ' ' . $this->cyan($this->bold($title)),
            $this->cyan('│'),
            $this->cyan('│  ') . $this->bold($company),
            $this->cyan('│  ') . $this->dim($duration),
            $this->cyan('│'),
            collect(explode(PHP_EOL, implode(PHP_EOL, $description)))
                ->map(fn ($line) => $this->cyan('│  ') . $line)
                ->implode(PHP_EOL),
            $this->cyan('│'),
            $this->cyan($position === 'last' ? '└─' : '│'),
        ])->implode(PHP_EOL);
    }

    protected function renderEducation(Resume $resume): array
    {
        return [
            $this->bold('Syracuse University'),
            'BFA, Drama',
            '2003 - 2007',
            '',
            $this->header('You Have... A Degree in Acting?'),
            '',
            $this->wrapped('Sure do, and it has served me well in my career as a software engineer.'),
            '',
            $this->wrapped('I started making websites in high school, in the early days of the modern internet. I was also interested in acting and dance. I pursued both passions at the same time.'),
            '',
            $this->wrapped('When I graduated college and started auditioning, instead of waiting tables I was a freelance web developer, creating websites for clients to pay my rent. When it was clear that acting was not going to work out, web development fully eclipsed it as my career, and the rest is hisory.'),
            '',
            $this->wrapped('I\'m a firm believer that the skills I learned as an actor have made me a better developer. I\'m a great communicator, I\'m comfortable in front of a crowd, and I\'m able to think on my feet.'),
            '',
            $this->wrapped('And I still love theater! My wife is an actor and most of my friends are in the arts in some way. Win win.'),
        ];
    }

    protected function renderSkills(Resume $resume): array
    {
        return [
            $this->header('Languages'),
            'PHP',
            'TypeScript',
            'JavaScript',
            'CSS',
            'HTML',
            'SQL',
            '',
            $this->header('Frameworks/Libraries/CMS'),
            'Laravel',
            'Vue',
            'Tailwind',
            'Inertia.js',
            'Statamic',
            'React',
            'PestPHP',
            'PHPUnit',
            'React Native',
            '',
            $this->header('Databases'),
            'MySQL',
            '',
            $this->header('Tools'),
            'DigitalOcean',
            'AWS',
            'Vite',
            'Jenkins',
            'GitHub Actions',
        ];
    }

    protected function renderInterests(Resume $resume): array
    {
        return [];
    }

    protected function wrapped(string $text): string
    {
        return wordwrap(
            $text,
            $this->prompt->maxTextWidth,
            PHP_EOL,
            true,
        );
    }

    protected function header(string $text): string
    {
        return collect([
            $this->cyan($this->bold($text)),
            $this->cyan($this->bold(str_repeat('-', strlen($text)))),
        ])->implode(PHP_EOL);
    }

    protected function subHeader(string $text): string
    {
        return $this->bold($text);
    }

    protected function project(string $name, string $url, string $description): string
    {
        return collect([
            $this->bold($name) . ' - ' . $this->cyan($url),
            '',
            $this->wrapped($description),
        ])->implode(PHP_EOL);
    }
}

// big
// doom
// modular
// small
// soft
// standard
// sub zero smush r
// tmplr
// shimrod
// thin