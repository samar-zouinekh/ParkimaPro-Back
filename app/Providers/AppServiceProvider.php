<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Stancl\Tenancy\Events\TenancyBootstrapped;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;


class AppServiceProvider extends ServiceProvider
{
       /**
     * The MobilityApp version.
     *
     * @var string
     */
    const VERSION = '1.0.0';
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // $this->app->register(\L5Swagger\L5SwaggerServiceProvider::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(TenancyBootstrapped::class, function (TenancyBootstrapped $event) {
            // group logs in folders by tenant identifier (storage/logs/tenant_folder/)
            config(['logging.channels.daily.path' => storage_path('logs/'.$event->tenancy->tenant->id.'/laravel.log')]);
            app()->instance('log', new \Illuminate\Log\LogManager(app()));

            $passportKey = base_path(config('passport.key_path').DIRECTORY_SEPARATOR.$event->tenancy->tenant->id);
            // create the directory if it does not exist to support the passport:install command
            if (! file_exists($passportKey)) {
                mkdir($passportKey, 0700, true);
            }
            // separate passport keys by tenant, each tenant have its own keys inside storage/keys/tenant_folder/
            // Passport::loadKeysFrom($passportKey);
        });
        \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::$subdomainIndex = 0;
        \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::$onFail = function ($exception, $request, $next) {
            return redirect(env('WRONG_SUBDOMAIN_REDIRECT_URL', 'http://api.demo.localhost'));
        };

        Schema::defaultStringLength(191);
    }
}
