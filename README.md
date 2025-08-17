## 📦 Installation

Install the module via Composer:
```bash
composer config repositories.gate vcs https://github.com/ostap-mykhaylyak/nube
```
```bash
composer require ostap-mykhaylyak/nube:dev-main
```

```php
use Ostap\Nube\Client;

// $ lxc config trust add → trust_token
$trust_token = "ABC123...";

$client = new Client('https://127.0.0.1:8443');

$response = $client->registerCertificateWithTrustToken(
    $trust_token,
    __DIR__.'/client.crt',
    __DIR__.'/client.key',
    'php-client'
);

print_r($response);

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

print_r($client->request('GET', '/1.0/containers'));
```

```php
use Ostap\Nube\Client;
use Ostap\Nube\Endpoint\Forward;

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

$forwardApi = new Forward($client);

// Configurazione del forward multi-porta
$newForward = [
    "config" => [
        "target_address" => "10.101.55.23"   // IP del container
    ],
    "description" => "Forward HTTP/HTTPS per container web01",
    "listen_address" => "203.0.113.45",      // IP pubblico del server
    "ports" => [
        [
            "description" => "HTTP pubblicato",
            "listen_port" => "80",
            "protocol" => "tcp",
            "target_address" => "10.101.55.23",
            "target_port" => "80"
        ],
        [
            "description" => "HTTPS pubblicato TCP",
            "listen_port" => "443",
            "protocol" => "tcp",
            "target_address" => "10.101.55.23",
            "target_port" => "443"
        ],
        [
            "description" => "HTTPS pubblicato UDP",
            "listen_port" => "443",
            "protocol" => "udp",
            "target_address" => "10.101.55.23",
            "target_port" => "443"
        ]
    ]
];

$response = $forwardApi->create("lxdbr0", $newForward);
print_r($response);
```
