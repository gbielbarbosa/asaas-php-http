# asaas-php-http

## Requisitos

- PHP >=7.2

## Instalação

```
$ composer require gbielbarbosa/asaas-php-http
```

Uma biblioteca de registro compatível com [psr/log](https://packagist.org/packages/psr/log) também é necessária. Recomendo [monolog](https://github.com/Seldaek/monolog) que será usado em exemplos.

## Uso

```php
<?php

include 'vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Asaas\Http\Http;
use Asaas\Http\Drivers\React;

$loop = \React\EventLoop\Factory::create();
$logger = (new Logger('logger-name'))->pushHandler(new StreamHandler('php://output'));
$http = new Http(
    'api_token',
    $production, // Parâmetro para definir o ambiente
    $loop,
    $logger
);

// configura um driver - este exemplo usa o React Driver
$driver = new React($loop);
$http->setDriver($driver);

// precisa ser a última linha
$loop->run();
```
Todos os métodos de solicitação têm os mesmos parâmetros.
```php
$http->get(string $url, $content = null, array $headers = []);
$http->post(string $url, $content = null, array $headers = []);
$http->put(string $url, $content = null, array $headers = []);
$http->patch(string $url, $content = null, array $headers = []);
$http->delete(string $url, $content = null, array $headers = []);
```
Para outros métodos:
```php
$http->queueRequest(string $method, string $url, $content, array $headers = []);
```
Todos os métodos retornam um JSON decodificado como objeto:
```php
$http->get('myAccount/accountNumber')->done(function ($response) {
    var_dump($response);
}, function ($e) {
    echo "Error: ".$e->getMessage().PHP_EOL;
});
```
Os endpoints do Asaas são fornecidos na classe Endpoint.php como constantes. Os parâmetros começam com dois pontos, por exemplo `payments/:id`. Você pode vincular parâmetros a eles com a mesma classe:
```php
$endpoint = Endpoint::bind(Endpoint::PAYMENTS_GET, 'payment_id');

$http->get($endpoint)->done(...);
```
Recomenda-se que, se o endpoint contiver parâmetros, você use a função Endpoint::bind() para classificar as solicitações em seus intervalos de limite de taxa corretos.

## Licença

Este software é licenciado sob a licença MIT, que pode ser visualizada no arquivo LICENSE.

## Créditos

- [Gabriel Barbosa](mailto:gabrielbarbosak1@gmail.com)