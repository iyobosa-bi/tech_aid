<?php

namespace App\Enums;

enum TicketCategory: string
{
    case Hardware = 'hardware';
    case Software = 'software';
    case Network = 'network';
    case Email = 'email';
    case Access = 'access';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Hardware => 'Hardware (laptop, printer, peripherals)',
            self::Software => 'Software / Application',
            self::Network => 'Network / Internet',
            self::Email => 'Email',
            self::Access => 'Access & Accounts',
            self::Other => 'Other',
        };
    }
}
