<?php

namespace App\Enums;

enum BookingStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Confirmed => 'Подтверждена',
            self::Cancelled => 'Отменена',
        };
    }

    /** Статусы, при которых столик считается занятым */
    public static function active(): array
    {
        return [self::New->value, self::Confirmed->value];
    }
}