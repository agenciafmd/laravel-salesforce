## Laravel - Salesforce

[//]: # ([![Downloads]&#40;https://img.shields.io/packagist/dt/agenciafmd/laravel-salesforce.svg?style=flat-square&#41;]&#40;https://packagist.org/packages/agenciafmd/laravel-rdstation&#41;)
[![Licença](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

- Envia as conversões para a Salesforce

## Instalação

```bash
composer require agenciafmd/laravel-salesforce:dev-master
```

## Configuração

Para que a integração seja realizada, precisamos da **url da API**

Por isso, é necessário colocar o endereço no nosso .env

```dotenv
SALESFORCE_API_URL=https://agenciafmd.secure.force.com/services/apexrest/LeadConnector
```
Caso seja necessária a autenticação, é necessário colocar fornecer os dados no nosso .env

```dotenv
SALESFORCE_API_AUTH=https://seu-endereco-para-gerar-token.sandbox.my.salesforce.com/services/oauth2/token
SALESFORCE_USERNAME=username@email.com
SALESFORCE_PASSWORD=sua_senha
SALESFORCE_CLIENT_ID=seu_client_id
SALESFORCE_CLIENT_SECRET=seu_client_secret
```

```dotenv
## Uso

Envie os campos no formato de array para o SendConversionsToSalesforce.

O campo **email** é obrigatório =)

Para que o processo funcione pelos **jobs**, é preciso passar os valores dos cookies conforme mostrado abaixo.

```php
use Agenciafmd\Salesforce\Jobs\SendConversionsToSalesforce;

$data['email'] = 'milena.ramiro@fmd.ag';

SendConversionsToSalesforce::dispatch($data + [
        'LastName' => 'Milena Ramiro',
        'MobilePhone' => '17982036569',
        'idEmpreendimentoInteresse' => 'b5t5f00016FAqSRavP',
        'utm_campaign' => Cookie::get('utm_campaign', ''),
        'utm_content' => Cookie::get('utm_content', ''),
        'utm_medium' => Cookie::get('utm_medium', ''),
        'utm_source' => Cookie::get('utm_source', ''),
        'utm_term' => Cookie::get('utm_term', ''),
    ])
    ->delay(5)
    ->onQueue('low');
```

Note que no nosso exemplo, enviamos o job para a fila **low**.

Certifique-se de estar rodando no seu queue:work esteja semelhante ao abaixo.

```shell
php artisan queue:work --tries=3 --delay=5 --timeout=60 --queue=high,default,low
```