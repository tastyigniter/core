<?php

namespace Igniter\Flame\Database;

use Igniter\Flame\Database\Attach\Media;
use Igniter\Flame\Database\Attach\Observers\MediaObserver;
use Igniter\Flame\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\DatabaseServiceProvider as BaseDatabaseServiceProvider;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Database\Eloquent\Relations\Relation;

class DatabaseServiceProvider extends BaseDatabaseServiceProvider
{
    /**
     * Register the service provider.
     * @return void
     */
    public function register()
    {
        Model::clearExtendedClasses();

        parent::register();
    }

    public function boot()
    {
        parent::boot();

        Media::observe(MediaObserver::class);

        Relation::morphMap([
            'media' => \Igniter\Flame\Database\Attach\Media::class,
        ]);
    }

    /**
     * Register the primary database bindings.
     *
     * @return void
     */
    protected function registerConnectionServices()
    {
        // The connection factory is used to create the actual connection instances on
        // the database. We will inject the factory into the manager so that it may
        // make the connections while they are actually needed and not of before.
        $this->app->singleton('db.factory', function($app) {
            return new ConnectionFactory($app);
        });

        // The database manager is used to resolve various connections, since multiple
        // connections might be managed. It also implements the connection resolver
        // interface which may be used by other components requiring connections.
        $this->app->singleton('db', function($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        $this->app->bind('db.connection', function($app) {
            return $app['db']->connection();
        });

        $this->app->singleton('db.transactions', function($app) {
            return new DatabaseTransactionsManager;
        });
    }
}
