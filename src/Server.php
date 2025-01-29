<?php

namespace IncusApi;

class Server extends Client
{
    public function getServerInfo(): array
    {
        return $this->sendRequest('GET', '/1.0');
    }

    public function updateServerConfig(array $data): array
    {
        return $this->sendRequest('PATCH', '/1.0', $data);
    }
}
