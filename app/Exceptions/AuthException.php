<?php

namespace App\Exceptions;

use Exception;

class AuthException extends Exception
{
    public function __construct($message = "", $code = 400, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public function render($request)
    {
        return response()->json(['error' => $this->getMessage()], $this->getCode());
    }
}
