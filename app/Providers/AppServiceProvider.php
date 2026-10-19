<?php

namespace App\Providers;

use App\Support\ItemLookups;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        ItemLookups::register();
    }
}
