<?php

namespace IncusApi\Exceptions;

class CurlException extends ApiException
{
    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        parent::__construct("cURL Error: $message", $code, $previous);
    }
}
