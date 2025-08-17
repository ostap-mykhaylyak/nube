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

    public function list(string $networkName, string $project = 'default', bool $detailed = true): array
    {
        $query = ['project' => $project];
        if ($detailed) {
            $query['recursion'] = 1;
        }

        return $this->client->request(
            'GET',
            "/1.0/networks/{$networkName}/forwards",
            $query
        );
    }

    public function info(string $networkName, string $listenAddress, string $project = 'default'): array
    {
        return $this->client->request(
            'GET',
            "/1.0/networks/{$networkName}/forwards/{$listenAddress}",
            ['project' => $project]
        );
    }

    public function create(string $networkName, array $forwardData, string $project = 'default'): array
    {
        return $this->client->request(
            'POST',
            "/1.0/networks/{$networkName}/forwards?project={$project}",
            $forwardData
        );
    }

    public function update(string $networkName, string $listenAddress, array $forwardData, string $project = 'default'): array
    {
        return $this->client->request(
            'PUT',
            "/1.0/networks/{$networkName}/forwards/{$listenAddress}?project={$project}",
            $forwardData
        );
    }

    public function delete(string $networkName, string $listenAddress, string $project = 'default'): array
    {
        return $this->client->request(
            'DELETE',
            "/1.0/networks/{$networkName}/forwards/{$listenAddress}",
            ['project' => $project]
        );
    }
}
