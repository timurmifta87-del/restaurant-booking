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
}