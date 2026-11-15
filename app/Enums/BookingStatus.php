<?php

namespace App\Enums;

enum BookingStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Cancelled = 'cancelled';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Новая',
            self::Confirmed => 'Подтверждена',
            self::Cancelled => 'Отменена',
            self::Completed => 'Завершена',
        };
    }

    /** Допустимые переходы: новая → подтверждена → завершена, отменить можно новую или подтверждённую */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Completed, self::Cancelled],
            self::Cancelled, self::Completed => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /** Статусы, при которых столик считается занятым */
    public static function active(): array
    {
        return [self::New->value, self::Confirmed->value];
    }
}
