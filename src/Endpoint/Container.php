<?php
namespace Ostap\Nube\Endpoint;

use Ostap\Nube\Client;

class Container
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): array
    {
        return $this->client->request('GET', '/1.0/containers');
    }

    public function get(string $name): array
    {
        return $this->client->request('GET', "/1.0/containers/{$name}");
    }

    public function create(string $name, array $config = []): array
    {
        $data = array_merge(['name' => $name], $config);
        return $this->client->request('POST', '/1.0/containers', $data);
    }

    public function delete(string $name): array
    {
        return $this->client->request('DELETE', "/1.0/containers/{$name}");
    }
}
