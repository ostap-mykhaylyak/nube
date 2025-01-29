<?php

namespace IncusApi;

class Instances extends Client
{
    public function listInstances(): array
    {
        return $this->sendRequest('GET', '/1.0/instances');
    }

    public function getInstance(string $name): array
    {
        return $this->sendRequest('GET', "/1.0/instances/$name");
    }

    public function createInstance(array $data): array
    {
        return $this->sendRequest('POST', '/1.0/instances', $data);
    }

    public function updateInstance(string $name, array $data): array
    {
        return $this->sendRequest('PATCH', "/1.0/instances/$name", $data);
    }

    public function deleteInstance(string $name): array
    {
        return $this->sendRequest('DELETE', "/1.0/instances/$name");
    }
}
