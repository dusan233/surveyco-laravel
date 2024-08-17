<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UnauthorizedException extends Exception
{
    public function __construct(
        string $message = 'This action is unauthorized',
        Throwable $previous = null,
        int $code = Response::HTTP_UNAUTHORIZED,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
