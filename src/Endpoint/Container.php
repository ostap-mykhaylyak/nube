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

    /**
     * Esegue un comando nel container e attende il completamento
     * 
     * @param string $name Nome del container
     * @param array $command Comando da eseguire (array di stringhe)
     * @param array $options Opzioni aggiuntive (environment, user, group, cwd, etc.)
     * @return array Risultato dell'esecuzione con stdout, stderr e return code
     */
    public function exec(string $name, array $command, array $options = []): array
    {
        // Prepara i parametri per l'esecuzione
        $execData = [
            'command' => $command,
            'wait-for-websocket' => false,
            'record-output' => true,
            'interactive' => false,
        ];

        // Aggiungi opzioni se presenti
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

        // Avvia l'esecuzione del comando
        $response = $this->client->request('POST', "/1.0/containers/{$name}/exec", $execData);
        
        // Debug della risposta iniziale
        if (!isset($response['body']) && !isset($response['operation']) && !isset($response['metadata'])) {
            throw new \RuntimeException('Struttura risposta exec non valida. Risposta ricevuta: ' . json_encode($response));
        }

        // Ottieni l'operation ID
        $operationId = $this->extractOperationId($response);

        // Attendi il completamento dell'operazione
        $this->waitForOperation($operationId);

        // Recupera l'output
        return $this->getOperationOutput($operationId);
    }

    /**
     * Esegue un comando semplice e ritorna solo lo stdout
     * 
     * @param string $name Nome del container
     * @param string|array $command Comando da eseguire
     * @return string Output del comando
     */
    public function execSimple(string $name, $command): string
    {
        $commandArray = is_array($command) ? $command : ['/bin/sh', '-c', $command];
        $result = $this->exec($name, $commandArray);
        
        return $result['output']['stdout'] ?? '';
    }

    /**
     * Estrae l'ID dell'operazione dalla risposta
     */
    protected function extractOperationId(array $response): string
    {
        // Il client potrebbe wrappare la risposta in un array con 'body'
        $data = $response['body'] ?? $response;
        
        // Prova diversi formati di risposta
        if (isset($data['operation'])) {
            return trim($data['operation'], '/');
        }
        
        if (isset($data['metadata']['id'])) {
            return $data['metadata']['id'];
        }
        
        if (isset($data['id'])) {
            return $data['id'];
        }
        
        throw new \RuntimeException('Operation ID non trovato nella risposta. Risposta ricevuta: ' . json_encode($response));
    }

    /**
     * Attende il completamento di un'operazione
     */
    protected function waitForOperation(string $operationId, int $timeout = 300): void
    {
        $start = time();
        
        while (true) {
            $response = $this->client->request('GET', "/{$operationId}");
            
            // Debug
            if (!isset($response['body']) && !isset($response['metadata'])) {
                throw new \RuntimeException('Struttura risposta non valida in waitForOperation. Risposta ricevuta: ' . json_encode($response));
            }
            
            $operation = $response['body'] ?? $response;
            
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

    /**
     * Recupera l'output di un'operazione completata
     */
    protected function getOperationOutput(string $operationId): array
    {
        $response = $this->client->request('GET', "/{$operationId}");
        
        // Debug completo della risposta
        error_log("DEBUG getOperationOutput - Risposta completa: " . json_encode($response));
        
        // Debug
        if (!isset($response['body']) && !isset($response['metadata'])) {
            throw new \RuntimeException('Struttura risposta non valida in getOperationOutput. Risposta ricevuta: ' . json_encode($response));
        }
        
        $operation = $response['body'] ?? $response;
        
        $metadata = $operation['metadata'] ?? [];
        $status = $metadata['status'] ?? 'Unknown';
        
        error_log("DEBUG - Status: {$status}");
        error_log("DEBUG - Metadata completo: " . json_encode($metadata));
        
        $result = [
            'status' => $status,
            'return_code' => $metadata['metadata']['return'] ?? -1,
            'output' => [
                'stdout' => '',
                'stderr' => ''
            ]
        ];

        error_log("DEBUG - Return code: " . $result['return_code']);
        error_log("DEBUG - Output keys disponibili: " . json_encode(array_keys($metadata['metadata']['output'] ?? [])));

        // Recupera stdout se disponibile
        if (isset($metadata['metadata']['output']['1'])) {
            try {
                $stdoutPath = "/{$operationId}/logs/1";
                error_log("DEBUG - Tentativo di recupero stdout da: {$stdoutPath}");
                $stdoutResponse = $this->client->request('GET', $stdoutPath);
                
                error_log("DEBUG - Risposta stdout: " . json_encode($stdoutResponse));
                
                // Debug stdout
                if (!is_string($stdoutResponse) && !isset($stdoutResponse['body'])) {
                    throw new \RuntimeException('Struttura risposta stdout non valida. Risposta ricevuta: ' . json_encode($stdoutResponse));
                }
                
                $stdout = $stdoutResponse['body'] ?? $stdoutResponse;
                $result['output']['stdout'] = is_string($stdout) ? $stdout : '';
                error_log("DEBUG - Stdout estratto: " . $result['output']['stdout']);
            } catch (\RuntimeException $e) {
                throw $e; // Rilancia le eccezioni di debug
            } catch (\Exception $e) {
                error_log("DEBUG - Errore recupero stdout: " . $e->getMessage());
            }
        } else {
            error_log("DEBUG - Nessun output stdout disponibile nei metadata");
        }

        // Recupera stderr se disponibile
        if (isset($metadata['metadata']['output']['2'])) {
            try {
                $stderrPath = "/{$operationId}/logs/2";
                error_log("DEBUG - Tentativo di recupero stderr da: {$stderrPath}");
                $stderrResponse = $this->client->request('GET', $stderrPath);
                
                // Debug stderr
                if (!is_string($stderrResponse) && !isset($stderrResponse['body'])) {
                    throw new \RuntimeException('Struttura risposta stderr non valida. Risposta ricevuta: ' . json_encode($stderrResponse));
                }
                
                $stderr = $stderrResponse['body'] ?? $stderrResponse;
                $result['output']['stderr'] = is_string($stderr) ? $stderr : '';
            } catch (\RuntimeException $e) {
                throw $e; // Rilancia le eccezioni di debug
            } catch (\Exception $e) {
                error_log("DEBUG - Errore recupero stderr: " . $e->getMessage());
            }
        }

        error_log("DEBUG - Risultato finale: " . json_encode($result));
        return $result;
    }

    /**
     * Avvia il container
     */
    public function start(string $name, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'start',
            'timeout' => $timeout
        ]);
    }

    /**
     * Ferma il container
     */
    public function stop(string $name, bool $force = false, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'stop',
            'timeout' => $timeout,
            'force' => $force
        ]);
    }

    /**
     * Riavvia il container
     */
    public function restart(string $name, bool $force = false, int $timeout = 30): array
    {
        return $this->client->request('PUT', "/1.0/containers/{$name}/state", [
            'action' => 'restart',
            'timeout' => $timeout,
            'force' => $force
        ]);
    }
}
