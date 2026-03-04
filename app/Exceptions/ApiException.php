<?php

namespace App\Exceptions;

use App\Enums\ApiErrorCode;
use Exception;

class ApiException extends Exception
{
    public function __construct(
        public ApiErrorCode $codeEnum,
        string $message,
        public int $httpStatus = 400,
        public array $details = []
    ) {
        parent::__construct($message);
    }
}
