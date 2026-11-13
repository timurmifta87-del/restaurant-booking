<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /** POST /api/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            ...$request->validated(),
            'role' => UserRole::Client,
        ]);

        Log::info('Регистрация пользователя', ['user_id' => $user->id]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api')->plainTextToken,
        ], 201);
    }

    /** POST /api/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            Log::warning('Неудачная попытка входа', ['email' => $request->validated('email')]);

            return response()->json(['message' => 'Неверный email или пароль'], 401);
        }

        Log::info('Вход пользователя', ['user_id' => $user->id]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken('api')->plainTextToken,
        ]);
    }

    /** POST /api/logout */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Вы вышли из системы']);
    }

    /** GET /api/user */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
