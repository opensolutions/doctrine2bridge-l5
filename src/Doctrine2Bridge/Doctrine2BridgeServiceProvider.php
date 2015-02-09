<?php

namespace Doctrine2Bridge;

use Auth;
use Config;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\Common\Cache\Cache;

use Doctrine\ORM\Mapping\ClassMetadataFactory;

use Doctrine2Bridge\EventListeners\TablePrefix;

use \Doctrine2Bridge\Support\Repository as D2Repository;

/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class Doctrine2BridgeServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Have we been configured?
     */
    private $configured = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot( \Doctrine\Common\Cache\Cache $d2cache )
    {
        // handle publishing of config file:
        $this->handleConfigs();

        if( !$this->configured ) {
            if( isset( $_SERVER['argv'][1] ) && $_SERVER['argv'][1] != 'vendor:publish' )
                echo "You must pubish the configuration files first: artisan vendor:publish\n";
            return;
        }

        $d2em = $this->app->make( \Doctrine\ORM\EntityManagerInterface::class );
        $d2em->getConfiguration()->setMetadataCacheImpl( $d2cache );
        $d2em->getConfiguration()->setQueryCacheImpl( $d2cache );
        $d2em->getConnection()->getConfiguration()->setResultCacheImpl( $d2cache );

        if( Config::get( 'd2bdoctrine.sqllogger.enabled' ) )
            $this->attachLogger( $d2em );

        if( Config::get( 'd2bdoctrine.auth.enabled' ) )
            $this->setupAuth();
    }



    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if( !Config::get( 'd2bdoctrine' ) ) {
            if( isset( $_SERVER['argv'][1] ) && $_SERVER['argv'][1] != 'vendor:publish' )
                echo "You must pubish the configuration files first: artisan vendor:publish\n";
            $this->configured = false;
            return;
        }

        $this->registerEntityManager();
        $this->registerClassMetadataFactory();
        $this->registerConsoleCommands();
        $this->registerRepositoryFacade();
        $this->registerFacades();
    }

    /**
     * The Entity Manager - why we're all here!
     */
    private function registerEntityManager()
    {
        $this->app->singleton( EntityManagerInterface::class, function( $app ) {

            $dconfig = new \Doctrine\ORM\Configuration;

            $driver = new \Doctrine\ORM\Mapping\Driver\XmlDriver(
                array( Config::get( 'd2bdoctrine.paths.xml_schema' ) )
            );

            $dconfig->setMetadataDriverImpl( $driver );

            $dconfig->setProxyDir(                 Config::get( 'd2bdoctrine.paths.proxies'      ) );
            $dconfig->setProxyNamespace(           Config::get( 'd2bdoctrine.namespaces.proxies' ) );
            $dconfig->setAutoGenerateProxyClasses( Config::get( 'd2bdoctrine.autogen_proxies'    ) );

            $lconfig = $this->laravelToDoctrineConfigMapper();

            //load prefix listener
            if( isset($lconfig['prefix']) && $lconfig['prefix'] && $lconfig['prefix'] !== '' ) {
                $tablePrefix = new TablePrefix( $lconfig['prefix']);
                $eventManager->addEventListener(Events::loadClassMetadata, $tablePrefix);
            }

            return EntityManager::create( $lconfig, $dconfig );
        });

    }

    /**
     * Register Facades to make developers' lives easier
     */
    private function registerFacades()
    {
        // Shortcut so developers don't need to add an Alias in app/config/app.php
        \App::booting( function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias( 'D2EM', 'Doctrine2Bridge\Support\Facades\Doctrine2' );
        });
    }

    /**
     * Register Laravel console commands
     */
    private function registerConsoleCommands()
    {
        $this->commands([
            "\Doctrine2Bridge\Console\Generators\All",
            "\Doctrine2Bridge\Console\Generators\Entities",
            "\Doctrine2Bridge\Console\Generators\Proxies",
            "\Doctrine2Bridge\Console\Generators\Repositories",

            "\Doctrine2Bridge\Console\Schema\Create",
            "\Doctrine2Bridge\Console\Schema\Drop",
            "\Doctrine2Bridge\Console\Schema\Update",
            "\Doctrine2Bridge\Console\Schema\Validate",
        ]);
    }

    /**
     * Metadata Factory - mainly used by schema console commands
     */
    private function registerClassMetadataFactory()
    {
        $this->app->singleton( ClassMetadataFactory::class, function( $app ) {
            return $app[EntityManagerInterface::class]->getMetadataFactory();
        });
    }


    private function registerRepositoryFacade()
    {
        $this->app->bind( D2Repository::class, function( $app ) {
            return new D2Repository;
        });

        \App::booting( function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias( 'D2R', 'Doctrine2Bridge\Support\Facades\Doctrine2Repository' );
        });

    }

    /**
     * Attach Laravel logging to Doctrine for debugging / profiling
     */
    private function attachLogger( $d2em )
    {
        $logger = new Logger\Laravel;
        if( Config::has( 'd2bdoctrine.sqllogger.level' ) )
            $logger->setLevel( Config::get( 'd2bdoctrine.sqllogger.level' ) );

        $d2em->getConnection()->getConfiguration()->setSQLLogger( $logger );
    }

    /**
     * Set up Laravel authentication via Doctrine2 provider
     */
    private function setupAuth()
    {
        Auth::extend( 'doctrine2bridge', function() {
            return new \Illuminate\Auth\Guard(
                new \Doctrine2Bridge\Auth\Doctrine2UserProvider(
                    \D2EM::getRepository( Config::get( 'd2bdoctrine.auth.entity' ) ),
                    new \Illuminate\Hashing\BcryptHasher
                ),
                \App::make('session.store')
            );
        });
    }


    /**
     * Publish configuration file
     */
    private function handleConfigs() {
        $configPath = __DIR__ . '/../config/d2bdoctrine.php';
        $this->publishes( [ $configPath => config_path('d2bdoctrine.php') ] );
        $this->mergeConfigFrom( $configPath, 'd2bdoctrine' );
    }


    /**
     * Convert Laravel5's database configuration to something what Doctrine2's
     * DBAL providers can use.
     *
     * @return array
     */
    private function laravelToDoctrineConfigMapper()
    {
        switch( Config::get( 'database.default' ) ) {
            case 'mysql':
                return [
                    'driver'   => 'pdo_mysql',
                    'dbname'   => Config::get( 'database.connections.mysql.database' ),
                    'user'     => Config::get( 'database.connections.mysql.username' ),
                    'password' => Config::get( 'database.connections.mysql.password' ),
                    'host'     => Config::get( 'database.connections.mysql.host'     ),
                    'charset'  => Config::get( 'database.connections.mysql.charset'  ),
                    'prefix'   => Config::get( 'database.connections.mysql.prefix'   ),
                ];
                break;

            case 'pgsql':
                return [
                    'driver'   => 'pdo_pgsql',
                    'dbname'   => Config::get( 'database.connections.pgsql.database' ),
                    'user'     => Config::get( 'database.connections.pgsql.username' ),
                    'password' => Config::get( 'database.connections.pgsql.password' ),
                    'host'     => Config::get( 'database.connections.pgsql.host'     ),
                    'charset'  => Config::get( 'database.connections.pgsql.charset'  ),
                    'prefix'   => Config::get( 'database.connections.pgsql.prefix'   ),
                ];
                break;

            case 'sqlite':
                return [
                    'driver'   => 'pdo_sqlite',
                    'path'     => Config::get( 'database.connections.sqlite.database' ),
                    'user'     => Config::get( 'database.connections.sqlite.username' ),
                    'password' => Config::get( 'database.connections.sqlite.password' ),
                    'prefix'   => Config::get( 'database.connections.sqlite.prefix'   ),
                ];
                break;

                default:
                    throw new Doctrine2Bridge\Exception\ImplementationNotFound( Config::get( 'database.default' ) );
        }
    }
}
