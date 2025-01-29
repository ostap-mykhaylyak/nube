<?php

namespace IncusApi\Exceptions;

class InvalidRequestException extends ApiException
{
    public function __construct(string $method)
    {
        parent::__construct("Unsupported HTTP method: $method");
    }
}
