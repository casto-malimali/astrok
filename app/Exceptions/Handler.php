<?php

namespace App\Exceptions;

use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<\Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [
        // Customize per your needs
    ];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<\Throwable>>
     */
    protected $dontReport = [
        ValidationException::class,
        AuthenticationException::class,
        AuthorizationException::class,
        NotFoundHttpException::class,
        ThrottleRequestsException::class,
    ];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        // You can add ->reportable(fn(Throwable $e) => ...) here if needed.
    }

    public function render($request, Throwable $e)
    {
        // Serve JSON for API calls or when the client asks for JSON
        $wantsJson = $request->expectsJson() || $request->is('api/*');

        if (!$wantsJson) {
            return parent::render($request, $e);
        }

        // 401 Unauthenticated
        if ($e instanceof AuthenticationException) {
            return $this->problem(401, 'Unauthenticated');
        }

        // 403 Forbidden (policies, gates)
        if ($e instanceof AuthorizationException) {
            return $this->problem(403, 'Forbidden', $e->getMessage() ?: null);
        }

        // 404 Not Found (route or model)
        if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
            return $this->problem(404, 'Not Found');
        }

        // 405 Method Not Allowed
        if ($e instanceof MethodNotAllowedHttpException) {
            return $this->problem(405, 'Method Not Allowed');
        }

        // 422 Validation errors
        if ($e instanceof ValidationException) {
            return response()->json([
                'type' => 'about:blank',
                'title' => 'Unprocessable Entity',
                'status' => 422,
                'errors' => $e->errors(),
            ], 422);
        }

        // 429 Rate limit
        if ($e instanceof ThrottleRequestsException) {
            return $this->problem(429, 'Too Many Requests');
        }

        // 400 Bad Request for DB/constraint errors (hide details)
        if ($e instanceof QueryException) {
            return $this->problem(400, 'Bad Request');
        }

        // Any other HttpException (with status code)
        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            $message = $e->getMessage() ?: 'HTTP Error';
            return $this->problem($status, $message);
        }

        // 500 Fallback (hide details unless local)
        $detail = app()->isLocal() ? $e->getMessage() : null;
        return $this->problem(500, 'Server Error', $detail);
    }

    /**
     * Small helper to build Problem Details style JSON.
     */
    protected function problem(int $status, string $title, ?string $detail = null, array $extra = [])
    {
        return response()->json(array_merge([
            'type' => 'about:blank',
            'title' => $title,
            'status' => $status,
        ], $detail ? ['detail' => $detail] : [], $extra), $status);
    }
}
