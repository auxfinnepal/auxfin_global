<?php

namespace Auxfin\Global;

use Illuminate\Support\ServiceProvider;
use Nuwave\Lighthouse\Events\RegisterDirectiveNamespaces;
use Nuwave\Lighthouse\Schema\Source\SchemaStitcher;
use Illuminate\Contracts\Events\Dispatcher as EventsDispatcher;

class GlobalServiceProvider extends ServiceProvider
{


    public function register(): void
    {

        $this->commands([
           \Auxfin\Global\Console\GlobalInstall::class,
        ]);


        $this->mergeConfigFrom(__DIR__.'/../config/global.php', 'global');


        $this->publishes([
            __DIR__.'/../config/global.php' => config_path('global.php'),
        ], 'global-config');
        $dispatcher = app(\Illuminate\Contracts\Events\Dispatcher::class);
        $dispatcher->listen(
            \Nuwave\Lighthouse\Events\BuildSchemaString::class,
            function (): string {
                $stitcher = new SchemaStitcher(__DIR__ . '/resources/graphql/schema.graphql');
                return $stitcher->getSchemaString();
            }
        );

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');


    }
    public function boot(EventsDispatcher $events)
    {
        $events->listen(RegisterDirectiveNamespaces::class, function () {
            return ['Auxfin\\Global\\GraphQL\\Directives'];
        });
    }

}
