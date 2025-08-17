<?php
namespace Ostap\Nube;

class Client
{
    protected string $baseUrl;
    protected array $headers;
    protected ?string $certFile = null;
    protected ?string $keyFile = null;
    protected ?string $caFile = null;

    public function __construct(string $baseUrl, array $headers = [], array $options = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->headers = array_merge([
            'Accept: application/json',
            'Content-Type: application/json'
        ], $headers);

        $this->certFile = $options['cert'] ?? null;
        $this->keyFile  = $options['key'] ?? null;
        $this->caFile   = $options['ca'] ?? null;
    }

    public function request(string $method, string $endpoint, array $data = []): array
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;

        if (in_array(strtoupper($method), ['GET', 'DELETE']) && !empty($data)) {
            $url .= '?' . http_build_query($data);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        // Se configurati, usa certificati client
        if ($this->certFile && $this->keyFile) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certFile);
            curl_setopt($ch, CURLOPT_SSLKEY, $this->keyFile);
        }

        if ($this->caFile) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caFile);
        } else {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        return [
            'status' => $httpCode,
            'body'   => json_decode($response, true)
        ];
    }

    /**
     * Registra un certificato client nel trust store di LXD usando un trust_token
     */
    public function registerCertificateWithTrustToken(
        string $trustToken,
        string $certFile,
        string $keyFile,
        string $name = 'php-client',
        array $projects = ['default'],
        bool $restricted = false
    ): array {
        // Genera certificato self-signed
        $certData = Certificate::generate($certFile, $keyFile, $name);

        // Corpo richiesta come da specifica LXD
        $body = [
            'type'        => 'client',
            'certificate' => base64_encode($certData['cert']),
            'name'        => $name,
            'projects'    => $projects,
            'restricted'  => $restricted,
            'trust_token' => $trustToken
        ];

        return $this->request('POST', '/1.0/certificates', $body);
    }
}
