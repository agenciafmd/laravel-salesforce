<?php

declare(strict_types=1);

namespace Agenciafmd\Salesforce\Jobs;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

final class SendConversionsToSalesforce implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected ?string $accessToken = null;

    public function __construct(protected array $data = []) {}

    public function handle(): ?bool
    {
        if (! config('laravel-salesforce.public_api_url')) {
            return false;
        }

        if (config('laravel-salesforce.public_api_auth')
            && config('laravel-salesforce.username')
            && config('laravel-salesforce.password')
            && config('laravel-salesforce.client_id')
            && config('laravel-salesforce.client_secret')) {
            $this->getAccessToken();
        }

        $client = $this->getClientRequest();
        $endpoint = config('laravel-salesforce.public_api_url');
        $client->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->accessToken,
            ],
            'json' => $this->data,
        ]);

        return null;
    }

    private function getAccessToken(): void
    {
        if ($this->accessToken !== null) {
            return;
        }

        $client = $this->getClientRequest();
        $endpoint = config('laravel-salesforce.public_api_auth');
        $formParams = [
            'username' => config('laravel-salesforce.username'),
            'password' => config('laravel-salesforce.password'),
            'grant_type' => 'password',
            'client_id' => config('laravel-salesforce.client_id'),
            'client_secret' => config('laravel-salesforce.client_secret'),
        ];

        $this->accessToken = Cache::remember('access_token', now()->addHour(),
            static function () use ($client, $endpoint, $formParams) {
                $response = $client->request('POST', $endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded',
                    ],
                    'form_params' => $formParams,
                ]);

                $responseBody = json_decode((string) $response->getBody());

                return $responseBody->access_token;
            });
    }

    private function getClientRequest(): Client
    {
        $logger = new Logger('Salesforce');
        $logger->pushHandler(new StreamHandler(storage_path('logs/salesforce-' . date('Y-m-d') . '.log')));

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter('{method} {uri} HTTP/{version} {req_body} | RESPONSE: {code} - {res_body}')
            )
        );

        return new Client([
            'timeout' => 60,
            'connect_timeout' => 60,
            'http_errors' => false,
            'verify' => false,
            'handler' => $stack,
        ]);
    }
}
