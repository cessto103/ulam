<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Services\PayMongoGateway;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Resend\Client as ResendClient;
use Resend\Contracts\Client as ResendClientContract;
use Resend\Transporters\HttpTransporter;
use Resend\ValueObjects\ApiKey;
use Resend\ValueObjects\Transporter\BaseUri;
use Resend\ValueObjects\Transporter\Headers;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, PayMongoGateway::class);

        // Override default Resend client binding to provide a Guzzle client.
        // No manual CA bundle override — that was previously hardcoded to a
        // local WAMP path, which broke on every other machine (including
        // live). Guzzle's default (no `verify` override) correctly uses
        // PHP's own CA configuration: curl.cainfo/openssl.cafile in php.ini
        // on Windows, the OS trust store automatically on Linux.
        $this->app->singleton(ResendClientContract::class, function () {
            $apiKey = ApiKey::from(config('resend.api_key') ?? '');
            $baseUri = BaseUri::from(getenv('RESEND_BASE_URL') ?: 'api.resend.com');
            $headers = Headers::withAuthorization($apiKey);

            $guzzle = new GuzzleClient();

            return new ResendClient(new HttpTransporter($guzzle, $baseUri, $headers));
        });

        $this->app->alias(ResendClientContract::class, 'resend');
        $this->app->alias(ResendClientContract::class, ResendClient::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
        Vite::prefetch(concurrency: 3);
    }
}
