<?php
namespace Ostap\Nube\Endpoint;

use Ostap\Nube\Client;

class Forward
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(string $containerName): array
    {
        return $this->client->request('GET', "/1.0/containers/{$containerName}/network");
    }

    public function create(string $containerName, array $config): array
    {
        return $this->client->request('POST', "/1.0/containers/{$containerName}/network", $config);
    }

    public function delete(string $containerName, string $forwardName): array
    {
        return $this->client->request('DELETE', "/1.0/containers/{$containerName}/network/{$forwardName}");
    }
}
