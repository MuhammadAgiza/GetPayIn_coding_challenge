<?php

namespace App\Exceptions;

use Illuminate\Foundation\Configuration\Exceptions;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Responses\ApiResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;

use Throwable;

class ApiExceptionsHandler
{

    public static function registerCallbacks(Exceptions $exceptions): void
    {
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $previous = $e->getPrevious();
                $message = 'Resource not found.';

                if ($previous instanceof ModelNotFoundException) {
                    $modelClass = $previous->getModel();
                    $modelName = class_basename($modelClass);
                    $message = "{$modelName} not found.";
                } else {
                    $exceptionMessage = $e->getMessage();
                    if (!empty($exceptionMessage) && $exceptionMessage !== 'Not Found') {
                        $message = $exceptionMessage;
                    }
                }

                return ApiResponse::notFound($message);
            }

        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ApiResponse::unauthorized('Unauthenticated.');
            }
        });

        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {

                $statusCode = ($e instanceof HttpExceptionInterface)
                    ? $e->getStatusCode()
                    : 500;

                $message = ($statusCode === 500 && !config('app.debug'))
                    ? 'Server Error'
                    : $e->getMessage();

                if (empty($message)) {
                    $message = 'An unexpected error occurred.';
                }

                return ApiResponse::error($message, $statusCode);
            }
        });


    }

}
