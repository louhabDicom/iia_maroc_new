<?php

namespace App\Enums;

enum EditionStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function label(string $locale = 'fr'): string
    {
        return __("edition.status.{$this->value}", [], $locale);
    }
}
