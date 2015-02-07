<?php

namespace Doctrine2Bridge\Console\Generators;


use Illuminate\Console\Command as LaravelCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\ORM\EntityManagerInterface;

use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\EntityRepositoryGenerator;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;

use Config;

/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * Generator console command based on work from:
 *     https://github.com/mitchellvanw/laravel-doctrine
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class Repositories extends LaravelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'd2b:generate:repositories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Doctrine2 repositories for the entities';

    /**
     * The Entity Manager
     *
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private $d2em;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $d2em)
    {
        parent::__construct();

        $this->d2em = $d2em;
    }

    public function fire()
    {
        $this->info('Starting repository generation....');

        // flush all generated and cached entities, etc
        \D2Cache::flushAll();

        try {
            $metadatas = $this->d2em->getMetadataFactory()->getAllMetadata();
        } catch( \Doctrine\Common\Persistence\Mapping\MappingException $e ) {
            if( $this->option( 'verbose' ) == 3 )
                throw $e;

            $this->error( "Caught Doctrine\Common\Persistence\Mapping\MappingException: " . $e->getMessage() );

            $this->info( "Re-optimizing:" );
            $this->call( 'optimize' );
            $this->comment( "*** You must now rerun this artisan command ***" );
            exit(-1);
        }

        if( empty($metadatas) ) {
            $this->error('No metadata found to generate entities.');
            return -1;
        }

        $directory = Config::get( 'd2bdoctrine.paths.repositories' );

        if( !$directory ) {
            $this->error('The entity directory has not been set.');
            return -1;
        }

        $numRepositories = 0;
        $generator = new EntityRepositoryGenerator();

        foreach ($metadatas as $metadata) {
            if ($metadata->customRepositoryClassName) {
                $this->line( sprintf('Processing repository "<info>%s</info>"', $metadata->customRepositoryClassName) );
                $generator->writeEntityRepositoryClass($metadata->customRepositoryClassName, $directory);
                $numRepositories++;
            }
        }

        if ($numRepositories) {
            $this->info( 'Repositories have been created.');
        } else {
            $this->info('No Repository classes were found to be processed.' );
        }
    }

}
