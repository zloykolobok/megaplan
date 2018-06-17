<?php
    namespace Zloykolobok\Megaplan;

    use Illuminate\Support\ServiceProvider;

    class MegaplanServiceProvider extends ServiceProvider
    {
        public function boot()
        {
            $this->publishes([__DIR__ . '/../config/megaplan.php' => config_path('megaplan.php')]);
        }

        public function register()
        {
        }

    }