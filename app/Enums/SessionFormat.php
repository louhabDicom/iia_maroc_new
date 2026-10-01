<?php

namespace App\Enums;

enum SessionFormat: string
{
    case Opening = 'opening';
    case Keynote = 'keynote';
    case Plenary = 'plenary';
    case Panel = 'panel';
    case Workshop = 'workshop';
    case InnovationLab = 'innovation_lab';
    case Award = 'award';
    case Break = 'break';

    public function label(string $locale = 'fr'): string
    {
        return __("format.{$this->value}", [], $locale);
    }

    /** Formats that belong to the main plenary track, not a parallel room. */
    public function isPlenaryTrack(): bool
    {
        return in_array($this, [
            self::Opening, self::Keynote, self::Plenary, self::Panel, self::Award,
        ], true);
    }

    /** Formats that occupy a breakout room and can run in parallel. */
    public function isParallelTrack(): bool
    {
        return in_array($this, [self::Workshop, self::InnovationLab], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Opening => 'heroicon-o-sparkles',
            self::Keynote => 'heroicon-o-microphone',
            self::Plenary => 'heroicon-o-presentation-chart-line',
            self::Panel => 'heroicon-o-users',
            self::Workshop => 'heroicon-o-wrench-screwdriver',
            self::InnovationLab => 'heroicon-o-light-bulb',
            self::Award => 'heroicon-o-trophy',
            self::Break => 'heroicon-o-coffee',
        };
    }

    /** @return array<string, string> */
    public static function options(string $locale = 'fr'): array
    {
        $out = [];
        foreach (self::cases() as $case) {
            $out[$case->value] = $case->label($locale);
        }

        return $out;
    }
}
