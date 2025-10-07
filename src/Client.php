<?php
namespace Ostap\Nube;

class Client
{
    protected string $baseUrl;
    protected array $headers;
    protected ?string $certFile = null;
    protected ?string $keyFile = null;
    protected ?string $caFile = null;
    protected bool $rawMode = false;

    /**
     * Initialize the HTTP client
     * 
     * @param string $baseUrl Base URL for all requests
     * @param array $headers Additional headers to include in requests
     * @param array $options Optional configuration (cert, key, ca files)
     */
    public function __construct(string $baseUrl, array $headers = [], array $options = [])
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        
        // Merge default JSON headers with custom headers
        $this->headers = array_merge([
            'Accept: application/json',
            'Content-Type: application/json'
        ], $headers);

        // SSL/TLS certificate options
        $this->certFile = $options['cert'] ?? null;
        $this->keyFile  = $options['key'] ?? null;
        $this->caFile   = $options['ca'] ?? null;
    }

    /**
     * Enable RAW mode for the next request
     * In RAW mode, JSON headers are removed and response is not decoded
     * 
     * @return self Returns instance for method chaining
     */
    public function raw(): self
    {
        $this->rawMode = true;
        return $this;
    }

    /**
     * Execute an HTTP request
     * 
     * @param string $method HTTP method (GET, POST, PUT, PATCH, DELETE)
     * @param string $endpoint API endpoint (will be appended to baseUrl)
     * @param array|string $data Request data (array will be JSON encoded unless in RAW mode)
     * @return array Returns array with 'status' (HTTP code) and 'body' (response data)
     * @throws \Exception If cURL request fails
     */
    public function request(string $method, string $endpoint, array|string $data = []): array
    {
        $ch = curl_init();
        $url = $this->baseUrl . $endpoint;
        $isRaw = $this->rawMode;
        
        // Reset raw mode after capturing the value (one-time use per request)
        $this->rawMode = false;

        // For GET and DELETE, append data as query string
        if (in_array(strtoupper($method), ['GET', 'DELETE']) && !empty($data)) {
            if (is_array($data)) {
                $url .= '?' . http_build_query($data);
            }
        }

        // Basic cURL configuration
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        // For POST, PUT, PATCH, set request body
        if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            if ($isRaw) {
                // RAW mode: send data as-is (string) or JSON encode if array
                curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
            } else {
                // Standard mode: always JSON encode
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        // Configure headers based on mode
        if ($isRaw) {
            // RAW mode: filter out default JSON headers, keep only custom ones
            $customHeaders = array_filter($this->headers, function($header) {
                return !str_starts_with($header, 'Accept: application/json') &&
                       !str_starts_with($header, 'Content-Type: application/json');
            });
            curl_setopt($ch, CURLOPT_HTTPHEADER, array_values($customHeaders));
        } else {
            // Standard mode: use all headers (including JSON defaults)
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }

        // Configure SSL/TLS client certificate authentication if provided
        if ($this->certFile && $this->keyFile) {
            curl_setopt($ch, CURLOPT_SSLCERT, $this->certFile);
            curl_setopt($ch, CURLOPT_SSLKEY, $this->keyFile);
        }

        // Configure CA certificate for SSL verification
        if ($this->caFile) {
            curl_setopt($ch, CURLOPT_CAINFO, $this->caFile);
        } else {
            // Disable SSL verification if no CA file provided (not recommended for production)
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Handle cURL errors
        if ($response === false) {
            throw new \Exception(curl_error($ch));
        }

        curl_close($ch);

        // Return response based on mode
        return [
            'status' => $httpCode,
            'body'   => $isRaw ? $response : json_decode($response, true) // RAW: string, Standard: decoded JSON
        ];
    }
}
