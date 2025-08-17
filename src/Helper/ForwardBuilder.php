<?php

namespace Ostap\Nube\Helper;

class ForwardBuilder
{
    private string $listenAddress;
    private string $targetAddress;
    private string $description;
    private array $ports = [];

    public function __construct(string $listenAddress, string $targetAddress, string $description = "")
    {
        $this->listenAddress = $listenAddress;
        $this->targetAddress = $targetAddress;
        $this->description   = $description;
    }

    /**
     * Aggiunge una porta da forwardare.
     *
     * @param int|string $listenPort Porta pubblica (può essere singola o range tipo "8080-8090")
     * @param string     $protocol   tcp|udp
     * @param int|string|null $targetPort Porta interna del container (default = uguale a listenPort)
     * @param string|null $description Descrizione opzionale
     * @return $this
     */
    public function addPort($listenPort, string $protocol, $targetPort = null, ?string $description = null): self
    {
        $this->ports[] = [
            "description"    => $description ?? "Forward porta {$listenPort}/{$protocol}",
            "listen_port"    => (string) $listenPort,
            "protocol"       => strtolower($protocol),
            "target_address" => $this->targetAddress,
            "target_port"    => (string) ($targetPort ?? $listenPort),
        ];

        return $this;
    }

    /**
     * Restituisce la configurazione completa per la chiamata API
     */
    public function build(): array
    {
        return [
            "config" => [
                "target_address" => $this->targetAddress
            ],
            "description"   => $this->description,
            "listen_address"=> $this->listenAddress,
            "ports"         => $this->ports
        ];
    }
}
