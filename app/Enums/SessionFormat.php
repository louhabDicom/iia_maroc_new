<?php

namespace App\Enums;

enum SessionFormat: string
{
    /**
     * Registration and networking.
     *
     * Added for the 2026 programme, which opens both days with an
     * "Accueil, inscription et networking" block. It is not a session and not a
     * break, so it was being mislabelled as one of those: attendees were told
     * the published 08:00-09:00 slot was a pause.
     *
     * It is deliberately in neither isPlenaryTrack() nor isParallelTrack().
     * Those answer "does this occupy the plenary room" and "can this run in
     * parallel"; a registration desk occupies neither, and putting it in
     * either list would have it counted with sessions it is not.
     */
    case Registration = 'registration';
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

    /** True for the blocks that are published for information but are not
     *  sessions anyone presents or attends as a session. */
    public function isNonSession(): bool
    {
        return in_array($this, [self::Registration, self::Break], true);
    }

    public function icon(): string
    {
        return match ($this) {
            self::Registration => 'heroicon-o-ticket',
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
