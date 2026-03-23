<?php

declare(strict_types=1);

namespace Agenciafmd\Salesforce\Providers;

use Illuminate\Support\ServiceProvider;
use Override;

final class SalesforceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        //
    }

    #[Override]
    public function register(): void
    {
        $this->loadConfigs();
    }

    protected function loadConfigs(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/laravel-salesforce.php', 'laravel-salesforce');
    }
}
