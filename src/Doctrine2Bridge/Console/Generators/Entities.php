<?php

namespace Doctrine2Bridge\Console\Generators;


use Illuminate\Console\Command as LaravelCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\ORM\EntityManagerInterface;

use Doctrine\ORM\Tools\Console\MetadataFilter;
use Doctrine\ORM\Tools\EntityGenerator;
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
class Entities extends LaravelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'd2b:generate:entities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Doctrine2 entities';

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
        $this->info('Starting entities generation....');

        // flush all generated and cached entities, etc
        \D2Cache::flushAll();

        $cmf = new DisconnectedClassMetadataFactory();
        $cmf->setEntityManager($this->d2em);
        $metadata = $cmf->getAllMetadata();

        if( empty($metadata) ) {
            $this->error('No metadata found to generate entities.');
            return -1;
        }

        $directory = Config::get( 'd2bdoctrine.paths.models' );

        if( !$directory ) {
            $this->error('The entity directory has not been set.');
            return -1;
        }

        $entityGenerator = new EntityGenerator();

        $entityGenerator->setGenerateAnnotations($this->option('generate-annotations'));
        $entityGenerator->setGenerateStubMethods($this->option('generate-methods'));
        $entityGenerator->setRegenerateEntityIfExists($this->option('regenerate-entities'));
        $entityGenerator->setUpdateEntityIfExists($this->option('update-entities'));
        $entityGenerator->setNumSpaces($this->option('num-spaces'));
        $entityGenerator->setBackupExisting(!$this->option('no-backup'));

        $this->info('Processing entities:');
        foreach ($metadata as $item) {
            $this->line($item->name);
        }

        try {
            $entityGenerator->generate($metadata, $directory);
            $this->info('Entities have been created.');
        } catch ( \ErrorException $e ) {
            if( $this->option( 'verbose' ) == 3 )
                throw $e;

            $this->error( "Caught ErrorException: " . $e->getMessage() );
            $this->info( "Re-optimizing:" );
            $this->call( 'optimize' );
            $this->comment( "*** You must now rerun this artisan command ***" );
            exit(-1);
        }
    }


    protected function getOptions()
    {
        return [
            [
                'generate-annotations', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should generate annotation metadata on entities.', false
            ],
            [
                'generate-methods', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should generate stub methods on entities.', true
            ],
            [
                'regenerate-entities', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should regenerate entity if it exists.', false
            ],
            [
                'update-entities', null, InputOption::VALUE_OPTIONAL,
                'Flag to define if generator should only update entity if it exists.', true
            ],
            [
                'extend', null, InputOption::VALUE_REQUIRED,
                'Defines a base class to be extended by generated entity classes.'
            ],
            [
                'num-spaces', null, InputOption::VALUE_REQUIRED,
                'Defines the number of indentation spaces', 4
            ],
            [
                'no-backup', null, InputOption::VALUE_NONE,
                'Flag to define if generator should avoid backuping existing entity file if it exists.'
            ]
        ];
    }


}
