<?php

namespace Artemis\Repository;

use Artemis\Repository\Commands\QueryCacheFlush;
use Artemis\Repository\Middleware\TagForRequestMiddleware;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Intervention\Image\ImageServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        $this->app->register(ImageServiceProvider::class);
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('tag-request', TagForRequestMiddleware::class);
    }

    /**
     * Boot service
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                QueryCacheFlush::class
            ]);
        }
    }
}
