<?php

namespace App\Providers;

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
        // Override default Resend client binding to provide a Guzzle client
        // with the correct CA bundle — needed on WAMP/Windows where PHP's
        // default CA store doesn't include the issuer for api.resend.com.
        $this->app->singleton(ResendClientContract::class, function () {
            $apiKey = ApiKey::from(config('resend.api_key') ?? '');
            $baseUri = BaseUri::from(getenv('RESEND_BASE_URL') ?: 'api.resend.com');
            $headers = Headers::withAuthorization($apiKey);

            $guzzle = new GuzzleClient([
                'verify' => 'C:\wamp64\bin\php\php8.2.18\cacert.pem',
            ]);

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
