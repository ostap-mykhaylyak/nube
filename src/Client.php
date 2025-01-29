<?php

namespace IncusApi;

use IncusApi\Exceptions\CurlException;
use IncusApi\Exceptions\InvalidRequestException;
use IncusApi\Exceptions\CertificateGenerationException;

class Client
{
    protected const DEFAULT_HEADERS = [
        'Content-Type: application/json',
    ];

    public function __construct(
        protected string $baseUrl,
        protected array $headers = self::DEFAULT_HEADERS,
        protected ?string $pemCertPath = null,
        protected ?string $pemKeyPath = null,
        protected ?string $caCertPath = null
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Genera un certificato PEM e una chiave privata.
     *
     * @throws CertificateGenerationException
     */
    public static function generateCertificate(
        string $commonName,
        string $certPath,
        string $keyPath,
        array $options = []
    ): void {
        // Configurazione predefinita per il certificato
        $defaultOptions = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];

        // Unisci le opzioni fornite con quelle predefinite
        $options = array_merge($defaultOptions, $options);

        // Genera una nuova coppia di chiavi
        $privateKey = openssl_pkey_new($options);
        if ($privateKey === false) {
            throw new CertificateGenerationException("Failed to generate private key.");
        }

        // Crea una richiesta di firma del certificato (CSR)
        $csr = openssl_csr_new([
            "CN" => $commonName, // Nome comune (Common Name)
        ], $privateKey, $options);

        if ($csr === false) {
            throw new CertificateGenerationException("Failed to generate CSR.");
        }

        // Auto-firma il certificato
        $cert = openssl_csr_sign($csr, null, $privateKey, 365, $options); // Valido per 1 anno

        // Esporta il certificato e la chiave privata
        if (!openssl_x509_export_to_file($cert, $certPath)) {
            throw new CertificateGenerationException("Failed to export certificate to file.");
        }

        if (!openssl_pkey_export_to_file($privateKey, $keyPath)) {
            throw new CertificateGenerationException("Failed to export private key to file.");
        }
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

        // Configurazione di base
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);

        // Configurazione del certificato PEM
        if ($this->pemCertPath) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->pemCertPath);
            if ($this->pemKeyPath) {
                curl_setopt($ch, CURLOPT_SSLKEY, $this->pemKeyPath);
            }
            if ($this->caCertPath) {
                curl_setopt($ch, CURLOPT_CAINFO, $this->caCertPath);
            }
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        // Configurazione del metodo HTTP
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
