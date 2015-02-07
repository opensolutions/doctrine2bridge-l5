<?php

namespace Doctrine2Bridge;

use Doctrine\Common\Cache\Cache;

use Config;

/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class Doctrine2CacheBridgeServiceProvider extends \Illuminate\Support\ServiceProvider
{

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        // handle the publishing of configuration file
        $this->handleConfigs();
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCache();

        // Shortcut so developers don't need to add an Alias in app/config/app.php
        \App::booting( function() {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias( 'D2Cache', 'Doctrine2Bridge\Support\Facades\Doctrine2Cache' );
        });

    }


    private function registerCache()
    {
        $this->app->singleton( Cache::class, function ($app) {

            $config = $this->laravelToDoctrineConfigMapper();

            $cacheClass = "\Doctrine\Common\Cache\\" . $config['type'];

            if(!class_exists($cacheClass))
                throw new Exception\ImplementationNotFound( $cacheClass );

            switch( $config['type'] )
            {
                case 'FilesystemCache':
                    $cache = new $cacheClass( $config['dir'] );
                    break;

                case 'MemcacheCache':
                    $cache = new $cacheClass;
                    $cache->setMemcache( $this->setupMemcache( $config ) );
                    break;
            }

            if( Config::has( 'd2bcache.namespace') )
                $cache->setNamespace( Config::get( 'd2bcache.namespace') );

            return $cache;
        });
    }

    /**
     * Setup the standard PHP memcache object implementation
     */
    private function setupMemcache( $config )
    {
        $memcache = new \Memcache;

        if( !isset( $config['servers'] ) || !count( $config['servers'] ) )
            throw new Exception\Configuration( 'No servers defined for Memcache in config/cache.php' );

        foreach( $config['servers'] as $server )
        {
            $memcache->addServer(
                $server['host'],
                isset( $server['port'] )         ? $server['port']         : 11211,
                isset( $server['persistent'] )   ? $server['persistent']   : false,
                isset( $server['weight'] )       ? $server['weight']       : 1,
                isset( $server['timeout'] )      ? $server['timeout']      : 1,
                isset( $server['retry_int'] )    ? $server['retry_int']    : 15
            );
        }

        return $memcache;
    }

    /**
     * Publish the configuration file
     */
    private function handleConfigs() {
        $configPath = __DIR__ . '/../config/d2bcache.php';
        $this->publishes( [ $configPath => config_path('d2bcache.php') ] );
        $this->mergeConfigFrom( $configPath, 'd2bcache' );
    }

    /**
     * Convert Laravel5's cache configuration to something what Doctrine2's
     * cache providers can use.
     *
     * @return array
     */
    private function laravelToDoctrineConfigMapper()
    {
        switch( Config::get( 'cache.default' ) ) {
            case 'file':
                return [
                    'type'      => 'FilesystemCache',
                    'dir'       => Config::get( 'cache.stores.file.path' ) . DIRECTORY_SEPARATOR . 'doctine2.cache'
                ];
                break;

            case 'array':
                return [
                    'type'      => 'ArrayCache',
                ];
                break;

            case 'memcached':
                return [
                    'type'      => 'MemcacheCache',
                    'servers'   => Config::get( 'cache.stores.memcached.servers' )
                ];
                break;

            default:
                throw new Exception\ImplementationNotFound( Config::get( 'cache.default' ) );

        }
    }

}
