<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();

        DB::prohibitDestructiveCommands(app()->isProduction());

        Date::use(CarbonImmutable::class);

        URL::forceHttps(app()->isProduction() || app()->environment('stage'));

        Vite::useWaterfallPrefetching(concurrency: 10);
        Vite::useAggressivePrefetching();
        Vite::usePrefetchStrategy('waterfall', ['concurrency' => 1]);
    }
}
