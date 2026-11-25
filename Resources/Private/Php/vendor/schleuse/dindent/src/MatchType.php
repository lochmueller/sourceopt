<?php

declare(strict_types=1);

namespace Gajus\Dindent;

enum MatchType
{
    case IndentDecrease;
    case IndentIncrease;
    case IndentKeep;

    public function asString(): string
    {
        return match ($this) {
            MatchType::IndentDecrease => 'DECREASE',
            MatchType::IndentIncrease => 'INCREASE',
            MatchType::IndentKeep     => 'KEEP',
        };
    }
}
