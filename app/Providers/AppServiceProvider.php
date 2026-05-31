<?php

namespace App\Providers;

use App\Events\ProductPublished;
use App\Listeners\NotifyFollowersOnProductPublish;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Product::class, ProductPolicy::class);
        Event::listen(ProductPublished::class, NotifyFollowersOnProductPublish::class);
    }
}
