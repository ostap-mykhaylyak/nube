## 📦 Installation

Install the module via Composer:
```bash
composer config repositories.nube vcs https://github.com/ostap-mykhaylyak/nube
```
```bash
composer require ostap-mykhaylyak/nube:dev-main
```
or
```json
{
    "require": {
        "ostap-mykhaylyak/nube": "dev-main"
    },
    "repositories": {
        "nube": {
            "type": "vcs",
            "url": "https://github.com/ostap-mykhaylyak/nube"
        }
    }
}
```

```php
<?php

use Ostap\Nube\Certificate;

require __DIR__ . '/vendor/autoload.php';

$certificate = new Certificate()->generate(__DIR__ . '/client.crt', __DIR__ . '/client.key', 'php-client');

print_r($certificate);
```

```php
<?php

use Ostap\Nube\Client;

require __DIR__ . '/vendor/autoload.php';

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

$body = [
    'type'        => 'client',
    'name'        => 'php-client',
    'trust_token' => 'trust_token' // $ lxc config trust add → trust_token
];

echo '<pre>';
var_dump($client->request('POST', '/1.0/certificates', $body));
echo '</pre>';

print_r($response);

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

print_r($client->request('GET', '/1.0/containers'));
```

```php
<?php

use Ostap\Nube\Client;
use Ostap\Nube\Endpoint\Container;

require __DIR__ . '/vendor/autoload.php';

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__ . '/client.crt',
    'key'  => __DIR__ . '/client.key'
]);

$container = new Container($client);

$cloud_init = <<<CLOUDINIT
#cloud-config
package_update: true
package_upgrade: true
packages:
  - software-properties-common
runcmd:
  - add-apt-repository -y ppa:nginx/stable
  - apt-get update
  - apt-get install -y nginx
  - systemctl enable nginx
  - systemctl start nginx
CLOUDINIT;

$config = [
    "name" => "web01",
    "source" => [
        "type" => "image",
        "mode" => "pull",
        "server" => "https://cloud-images.ubuntu.com/releases",
        "protocol" => "simplestreams",
        "alias" => "24.04"
    ],
    "config" => [
        "cloud-init.user-data" => $cloud_init
    ],
    "profiles" => ["default"],
    "description" => "Ubuntu 24.04 with NGINX",
    "ephemeral" => false
];

$response = $container->create($config);
print_r($response);
```
```php
<?php

use Ostap\Nube\Client;
use Ostap\Nube\Endpoint\Forward;
use Ostap\Nube\Helper\ForwardBuilder;

require __DIR__ . '/vendor/autoload.php';

$client = new Client('https://127.0.0.1:8443', [], [
    'cert' => __DIR__.'/client.crt',
    'key'  => __DIR__.'/client.key'
]);

$forward = new Forward($client);

// === Forward IPv4 ===
$forwardIPv4 = (new ForwardBuilder(
    "203.0.113.45",       // IP pubblico IPv4 del server
    "10.101.55.23",       // IP interno del container (LXD IPv4)
    "Forward IPv4 HTTP/HTTPS per container web01"
))
    ->addPort(80,  "tcp", 80, "HTTP IPv4")
    ->addPort(443, "tcp", 443, "HTTPS IPv4 TCP")
    ->addPort(443, "udp", 443, "HTTPS IPv4 UDP")
    ->build();

$responseV4 = $forward->create("lxdbr0", $forwardIPv4);
print_r($responseV4);

// === Forward IPv6 ===
$forwardIPv6 = (new ForwardBuilder(
    "[2001:db8::1234]",     // IP pubblico IPv6 del server
    "fd42:1234:5678:1::2",  // IP interno del container (LXD IPv6)
    "Forward IPv6 HTTP/HTTPS per container web01"
))
    ->addPort(80,  "tcp", 80, "HTTP IPv6")
    ->addPort(443, "tcp", 443, "HTTPS IPv6 TCP")
    ->addPort(443, "udp", 443, "HTTPS IPv6 UDP")
    ->build();

$responseV6 = $forward->create("lxdbr0", $forwardIPv6);
print_r($responseV6);
```


```bash
$ lxc config trust list-tokens # List trusted clients
$ lxc config trust list # List all active certificate add tokens
```
