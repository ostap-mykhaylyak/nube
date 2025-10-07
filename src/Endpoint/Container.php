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

    public function state(string $name): array
    {
        return $this->client->request('GET', "/1.0/containers/{$name}/state");
    }

    public function exec(string $name, array $command, array $options = []): array
    {
        $execData = [
            'command' => $command,
            'wait-for-websocket' => false,
            'record-output' => true,
            'interactive' => false,
        ];

        if (isset($options['environment'])) {
            $execData['environment'] = $options['environment'];
        }
        if (isset($options['user'])) {
            $execData['user'] = $options['user'];
        }
        if (isset($options['group'])) {
            $execData['group'] = $options['group'];
        }
        if (isset($options['cwd'])) {
            $execData['cwd'] = $options['cwd'];
        }
        $response = $this->client->request('POST', "/1.0/containers/{$name}/exec", $execData);
        
        if (!isset($response['body']) && !isset($response['operation']) && !isset($response['metadata'])) {
            throw new \RuntimeException(json_encode($response, true));
        }

        return $response;
    }

    public function log(string $name, string $id, $output = 'stdout'): array
    {
        return $this->client->raw()->request('GET', "/1.0/instances/{$name}/logs/exec-output/{$id}.{$output}");
    }

    public function start(string $name, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'start',
            'timeout' => $timeout
        ]);
    }

    public function stop(string $name, bool $force = false, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'stop',
            'timeout' => $timeout,
            'force' => $force
        ]);
    }

    public function restart(string $name, bool $force = false, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'restart',
            'timeout' => $timeout,
            'force' => $force
        ]);
    }
}
