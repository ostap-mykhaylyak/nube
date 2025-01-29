<?php

namespace IncusApi\Exceptions;

class CertificateGenerationException extends ApiException
{
    public function __construct(string $message, int $code = 0, Exception $previous = null)
    {
        parent::__construct("Certificate Generation Error: $message", $code, $previous);
    }
}
