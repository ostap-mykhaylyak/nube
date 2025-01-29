<?php

namespace IncusApi;

class Certificates extends Client
{
    public function listCertificates(): array
    {
        return $this->sendRequest('GET', '/1.0/certificates');
    }

    public function getCertificate(string $name): array
    {
        return $this->sendRequest('GET', "/1.0/certificates/$name");
    }

    public function addCertificate(array $data): array
    {
        return $this->sendRequest('POST', '/1.0/certificates', $data);
    }

    public function updateCertificate(string $name, array $data): array
    {
        return $this->sendRequest('PATCH', "/1.0/certificates/$name", $data);
    }

    public function deleteCertificate(string $name): array
    {
        return $this->sendRequest('DELETE', "/1.0/certificates/$name");
    }
}
