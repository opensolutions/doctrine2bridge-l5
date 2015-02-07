<?php

namespace Doctrine2Bridge\Console\Schema;


use Illuminate\Console\Command as LaravelCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

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
class Update extends LaravelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'd2b:schema:update';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the database schema';


    /**
     * The schema tool.
     *
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    private $tool;

    /**
     * The class metadata factory
     *
     * @var \Doctrine\ORM\Tools\SchemaTool
     */
    private $metadata;

    public function __construct(SchemaTool $tool, ClassMetadataFactory $metadata)
    {
        parent::__construct();
        $this->tool = $tool;
        $this->metadata = $metadata;
    }


    public function fire()
    {

        $this->info('Checking if database needs updating....');

        $sql = $this->tool->getUpdateSchemaSql( $this->metadata->getAllMetadata(), $this->option('clean') );

        if( empty($sql) ) {
            $this->info('No updates found.');
            return;
        }

        if( $this->option( 'sql' ) ) {
            $this->info('Outputting update query:');
            $this->info(implode(';' . PHP_EOL, $sql) .';');
        } else if( $this->option( 'commit' ) ) {
            $this->info('Updating database schema....');
            $this->tool->updateSchema($this->metadata->getAllMetadata());
            $this->info('Schema has been updated!');
        } else {
            $this->comment( "Warning: this command can cause data loss. Run with --sql or --commit." );
        }
    }

    protected function getOptions()
    {
        return [
            ['sql', false, InputOption::VALUE_NONE, 'Dumps SQL query and does not execute creation.'],
            ['commit', false, InputOption::VALUE_NONE, 'Executes database schema creation.'],
            ['clean', null, InputOption::VALUE_OPTIONAL, 'When using clean, models all non-relevant to current metadata will be cleared.']

        ];
    }

}
