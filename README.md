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
