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
use Ostap\Nube\Helper\ForwardBuilder;

$client = new Client('https://[::1]:8443', [], [ // IPv6 localhost per esempio
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

$forwardApi = new Forward($client);

// Forward builder: esponiamo HTTP/HTTPS (TCP e UDP) su IPv6 pubblico
$builder = new ForwardBuilder(
    "[2001:db8::1234]",   // IP pubblico IPv6 del server
    "fd42:1234:5678:1::2", // IP IPv6 del container LXD
    "Forward IPv6 HTTP/HTTPS per container web01"
);

$builder
    ->addPort(80,  "tcp", 80, "HTTP IPv6")
    ->addPort(443, "tcp", 443, "HTTPS IPv6 TCP")
    ->addPort(443, "udp", 443, "HTTPS IPv6 UDP");

$newForward = $builder->build();

$response = $forwardApi->create("lxdbr0", $newForward);
print_r($response);

```
