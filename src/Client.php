<?php

namespace IncusApi;

use IncusApi\Exceptions\CurlException;
use IncusApi\Exceptions\InvalidRequestException;

class Client
{
    protected const DEFAULT_HEADERS = [
        'Content-Type: application/json',
    ];

    public function __construct(
        protected string $baseUrl,
        protected array $headers = self::DEFAULT_HEADERS
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Invia una richiesta HTTP.
     *
     * @throws CurlException
     * @throws InvalidRequestException
     */
    protected function sendRequest(string $method, string $endpoint, array $data = []): array
    {
        $url = $this->baseUrl . $endpoint;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        match ($method) {
            'GET' => null,
            'POST' => curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($data),
            ]),
            'PUT', 'PATCH', 'DELETE' => curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_POSTFIELDS => json_encode($data),
            ]),
            default => throw new InvalidRequestException($method),
        };

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new CurlException($error);
        }

        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}
