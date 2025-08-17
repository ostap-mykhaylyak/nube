## 📦 Installation

Install the module via Composer:
```bash
composer config repositories.gate vcs https://github.com/ostap-mykhaylyak/nube
```
```bash
composer require ostap-mykhaylyak/nube:dev-main
```
```bash
lxc config trust add
```

```php
use Ostap\Nube\Client;
use Ostap\Nube\Endpoint\Container;

$client = new Client('https://127.0.0.1:8443', [
    'Authorization: Bearer YOUR_LXD_TOKEN'
]);

$containerApi = new Container($client);
print_r($containerApi->list());
```
