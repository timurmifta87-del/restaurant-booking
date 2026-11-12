<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Для адресов /api/* всегда отвечаем JSON, даже без заголовка Accept
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 422: ошибка валидации
        $exceptions->render(function (ValidationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'Ошибка валидации',
                'errors' => $e->errors(),
            ], 422);
        });

        // 401: нет авторизации
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Требуется авторизация'], 401);
        });

        // 404: запись или маршрут не найдены
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $previous = $e->getPrevious();

            if ($previous instanceof ModelNotFoundException) {
                $labels = [
                    'Hall' => 'Зал не найден',
                    'Table' => 'Столик не найден',
                    'Booking' => 'Бронь не найдена',
                    'Dish' => 'Блюдо не найдено',
                    'MenuCategory' => 'Категория меню не найдена',
                    'User' => 'Пользователь не найден',
                ];
                $message = $labels[class_basename($previous->getModel())] ?? 'Запись не найдена';
            } elseif ($e->getMessage() === '' || str_starts_with($e->getMessage(), 'The route')) {
                $message = 'Маршрут не найден';
            } else {
                $message = $e->getMessage();
            }

            return response()->json(['message' => $message], 404);
        });

        // Остальные HTTP-ошибки: 403, 405, 409, 429 и т. д.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $defaults = [
                400 => 'Некорректный запрос',
                401 => 'Требуется авторизация',
                403 => 'Недостаточно прав',
                405 => 'Метод не поддерживается',
                409 => 'Конфликт данных',
                429 => 'Слишком много запросов',
            ];
            $status = $e->getStatusCode();
            $message = $e->getMessage() !== ''
                ? $e->getMessage()
                : ($defaults[$status] ?? 'Ошибка запроса');

            return response()->json(['message' => $message], $status);
        });

        // 500: непредвиденная ошибка, без стека вызовов и SQL
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Внутренняя ошибка сервера'], 500);
        });
    })->create();