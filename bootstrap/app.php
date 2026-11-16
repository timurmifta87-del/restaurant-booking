<?php

use App\Exceptions\BusinessException;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\LogApiRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        // Проверка роли в маршрутах: ->middleware('role:admin')
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Журнал всех запросов к API
        $middleware->api(append: [
            LogApiRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Нарушения бизнес-правил и так отдаются клиенту, в laravel.log их не дублируем
        $exceptions->dontReport([BusinessException::class]);

        // Для адресов /api/* всегда отвечаем JSON, даже без заголовка Accept
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        // 409: нарушение бизнес-правила
        $exceptions->render(function (BusinessException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            Log::warning('Нарушение бизнес-правила', [
                'message' => $e->getMessage(),
                'url' => $request->path(),
                'user_id' => $request->user()?->id,
            ]);

            return response()->json(['message' => $e->getMessage()], $e->status());
        });

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

        // 401: нет токена, токен неверный или истёк
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Требуется авторизация'], 401);
        });

        // 403: политика запретила действие (Gate::authorize)
        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Недостаточно прав'], 403);
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

        // Остальные HTTP-ошибки: 405, 409, 429 и т. д.
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
            $message = $e->getMessage() !== '' && ! str_contains($e->getMessage(), 'The ')
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
