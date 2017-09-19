<?php
    namespace Zloykolobok\Megaplan;

    use Illuminate\Support\ServiceProvider;

    class MegaplanServiceProvider extends ServiceProvider
    {
        public function boot()
        {
            $this->publishes([__DIR__ . '/../config/' => config_path() . "/"], 'config');
        }

        public function register() 
        {
        }

    }