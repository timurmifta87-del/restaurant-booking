<?php

namespace App\Http\Controllers\Api\Concerns;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * ВРЕМЕННО, до ПР8: пока нет авторизации, пользователя берём из user_id.
 * В ПР8 трейт удаляется, везде используется $request->user().
 */
trait ResolvesActor
{
    protected function actor(Request $request): User
    {
        if ($request->user()) {
            return $request->user();
        }

        $id = $request->input('user_id');
        abort_if(! $id, 401, 'Требуется авторизация (временно передайте user_id).');

        return User::findOrFail($id);
    }
}