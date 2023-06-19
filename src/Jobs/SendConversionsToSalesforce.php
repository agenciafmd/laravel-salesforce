<?php

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
use Illuminate\Support\Facades\Http;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Cache;
use Monolog\Logger;

class SendConversionsToSalesforce implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;
    protected $access_token;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function handle()
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

        $response = $client->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->access_token
            ],
            'json' => $this->data
        ]);
    }

    private function getAccessToken()
    {
        if (isset($this->access_token)) {
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

        $this->access_token = Cache::remember('access_token', now()->addHours(1),
            function () use ($client, $endpoint, $formParams) {
                $response = $client->request('POST', $endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/x-www-form-urlencoded'
                    ],
                    'form_params' => $formParams
                ]);

                $responseBody = json_decode($response->getBody());
                return $responseBody->access_token;
            });
    }

    private function getClientRequest()
    {
        $logger = new Logger('Salesforce');
        $logger->pushHandler(new StreamHandler(storage_path('logs/salesforce-' . date('Y-m-d') . '.log')));

        $stack = HandlerStack::create();
        $stack->push(
            Middleware::log(
                $logger,
                new MessageFormatter("{method} {uri} HTTP/{version} {req_body} | RESPONSE: {code} - {res_body}")
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
