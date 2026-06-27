<?php

namespace App\Providers;

use App\Domain\Integrations\Guardian\Adapters\GuardianAdapterInterface;
use App\Domain\Integrations\Guardian\Adapters\GuardianMockAdapter;
use App\Domain\Integrations\Guardian\Adapters\GuardianSoapAdapter;
use App\Domain\Integrations\Protheus\Adapters\ProtheusAdapterInterface;
use App\Domain\Integrations\Protheus\Adapters\ProtheusHttpAdapter;
use App\Domain\Integrations\Protheus\Adapters\ProthousMockAdapter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            GuardianAdapterInterface::class,
            fn () => config('integrations.guardian.mock')
                ? new GuardianMockAdapter()
                : new GuardianSoapAdapter(),
        );

        $this->app->bind(
            ProtheusAdapterInterface::class,
            fn () => config('integrations.protheus.mock')
                ? new ProthousMockAdapter()
                : new ProtheusHttpAdapter(),
        );
    }

    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
