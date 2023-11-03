<?php

namespace Chewie\Concerns;

use Illuminate\Support\Collection;

trait DrawsBigNumbers
{
    protected string $bigNumbers = <<<'NUMBERS'
    ┏┓
    ┃┫
    ┗┛
    ┓
    ┃
    ┻
    ┏┓
    ┏┛
    ┗━
    ┏┓
     ┫
    ┗┛
    ┏┓
    ┃┃
    ┗╋
    ┏━
    ┗┓
    ┗┛
    ┏┓
    ┣┓
    ┗┛
    ━┓
     ┃
     ╹
    ┏┓
    ┣┫
    ┗┛
    ┏┓
    ┗┫
    ┗┛
    NUMBERS;

    public function bigNumber($number): Collection
    {
        $numbers = collect(explode("\n", $this->bigNumbers))->chunk(3);

        $number = str_split($number);

        $bigNumbers = collect($number)->map(fn ($digit) => $numbers[$digit]);

        if ($bigNumbers->count() === 1) {
            return $bigNumbers->first();
        }

        return collect($bigNumbers->shift())->zip(...$bigNumbers)->map(fn ($digits) => $digits->implode(''));
    }
}
