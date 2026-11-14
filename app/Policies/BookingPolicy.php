<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /** Администратору разрешено всё */
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    /** Изменение предзаказа и отмена — только владелец */
    public function update(User $user, Booking $booking): bool
    {
        return $booking->user_id === $user->id;
    }

    /** Смена статуса — только администратор (before вернёт true) */
    public function changeStatus(User $user, Booking $booking): bool
    {
        return false;
    }
}
