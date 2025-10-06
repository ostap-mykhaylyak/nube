<?php
namespace Ostap\Nube\Endpoint;

use Ostap\Nube\Client;

class Operation
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function list(): array
    {
        return $this->client->request('GET', '/1.0/operations');
    }

    public function get(string $id): array
    {
        $id = ltrim($id, '/');
        if (!str_starts_with($id, '1.0/operations/')) {
            $id = '1.0/operations/' . $id;
        }
        
        return $this->client->request('GET', "/{$id}");
    }

    public function cancel(string $id): array
    {
        $id = ltrim($id, '/');
        if (!str_starts_with($id, '1.0/operations/')) {
            $id = '1.0/operations/' . $id;
        }
        
        return $this->client->request('DELETE', "/{$id}");
    }

    public function wait(string $id, int $timeout = 300): array
    {
        return $this->client->request('GET', "/1.0/operations/{$id}/wait?timeout={$timeout}");
    }

    public function getLog(string $id, string $logPath): array
    {
        return $this->client->request('GET', $logPath);
    }
}
