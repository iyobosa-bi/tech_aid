<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function hint(): string
    {
        return match ($this) {
            self::Low => 'Minor inconvenience, work can continue',
            self::Medium => 'Slows down my work',
            self::High => 'I cannot work at all',
        };
    }
}
