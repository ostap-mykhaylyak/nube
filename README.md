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

// struttura forward come da API LXD
$newForward = [
    "config" => [
        "target_address" => "198.51.100.99",
        "user.mykey" => "foo"
    ],
    "description" => "My public IP forward",
    "listen_address" => "192.0.2.1",
    "ports" => [
        [
            "description" => "My web server forward",
            "listen_port" => "80,81,8080-8090",
            "protocol" => "tcp",
            "target_address" => "198.51.100.2",
            "target_port" => "80,81,8080-8090"
        ]
    ]
];

$response = $forwardApi->create("lxdbr0", $newForward);
print_r($response);
```
