<?php

namespace App\Exceptions;

use App\Enums\ResponseMessage;
use App\Traits\ResponseAPI;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Throwable;

class Handler extends ExceptionHandler
{
    use ResponseAPI;

    protected $dontReport = [];
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    /**
     * Override render() to handle JWT and Unauthenticated errors cleanly.
     */
    public function render($request, Throwable $e)
    {
        // dd('here', $request->all());
        // ✅ JWT & Unauthorized exceptions
        if ($e instanceof TokenExpiredException) {
            return $this->error('Token has expired', 401);
        }

        if ($e instanceof TokenInvalidException) {
            return $this->error('Token is invalid', 401);
        }

        if ($e instanceof JWTException) {
            return $this->error(ResponseMessage::EMAIL_NOT_FOUND, 401);
        }

        if ($e instanceof UnauthorizedHttpException) {
            return $this->error(ResponseMessage::UNAUTHORIZED, 401);
        }

        if ($e instanceof AuthenticationException) {
            return $this->error('Unauthorized', 401);
        }

        // ✅ Log everything else daily
        Log::channel('daily')->error('Exception: ' . $e->getMessage(), [
            'file'  => $e->getFile(),
            'line'  => $e->getLine(),
            'url'   => $request->fullUrl(),
            'input' => $request->all(),
        ]);

        // Generic error
        return $this->error(ResponseMessage::ERROR);
    }

    /**
     * Disable redirect-to-login behavior
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        return $this->error('Unauthorized', 401);
    }
}
