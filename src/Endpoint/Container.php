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

        $operationId = $this->extractOperationId($response);

        $this->waitForOperation($operationId);

        return $this->getOperationOutput($operationId);
    }

    public function execSimple(string $name, $command): string
    {
        $commandArray = is_array($command) ? $command : ['/bin/sh', '-c', $command];
        $result = $this->exec($name, $commandArray);
        
        return $result['output']['stdout'] ?? '';
    }

    protected function extractOperationId(array $response): string
    {
        // Prova diversi formati di risposta
        if (isset($response['operation'])) {
            return trim($response['operation'], '/');
        }
        
        if (isset($response['metadata']['id'])) {
            return $response['metadata']['id'];
        }
        
        if (isset($response['id'])) {
            return $response['id'];
        }
        
        // Debug: mostra la struttura della risposta
        throw new \RuntimeException('Operation ID non trovato nella risposta. Risposta ricevuta: ' . json_encode($response));
    }

    protected function waitForOperation(string $operationId, int $timeout = 300): void
    {
        $start = time();
        
        while (true) {
            $operation = $this->client->request('GET', "/{$operationId}");
            
            $status = $operation['metadata']['status'] ?? 'Unknown';
            
            if ($status === 'Success' || $status === 'Failure') {
                return;
            }
            
            if (time() - $start > $timeout) {
                throw new \RuntimeException('Timeout in attesa del completamento dell\'operazione');
            }
            
            usleep(100000); // 100ms
        }
    }

    protected function getOperationOutput(string $operationId): array
    {
        $operation = $this->client->request('GET', "/{$operationId}");
        
        $metadata = $operation['metadata'] ?? [];
        $status = $metadata['status'] ?? 'Unknown';
        
        $result = [
            'status' => $status,
            'return_code' => $metadata['metadata']['return'] ?? -1,
            'output' => [
                'stdout' => '',
                'stderr' => ''
            ]
        ];

        if (isset($metadata['metadata']['output']['1'])) {
            try {
                $stdoutPath = "/{$operationId}/logs/1";
                $stdout = $this->client->request('GET', $stdoutPath);
                $result['output']['stdout'] = is_string($stdout) ? $stdout : '';
            } catch (\Exception $e) {
                // Stdout non disponibile
            }
        }

        if (isset($metadata['metadata']['output']['2'])) {
            try {
                $stderrPath = "/{$operationId}/logs/2";
                $stderr = $this->client->request('GET', $stderrPath);
                $result['output']['stderr'] = is_string($stderr) ? $stderr : '';
            } catch (\Exception $e) {
                // Stderr non disponibile
            }
        }

        return $result;
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
