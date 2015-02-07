<?php

namespace Doctrine2Bridge\Console\Schema;


use Illuminate\Console\Command as LaravelCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\ORM\Tools\SchemaValidator;


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
class Validate extends LaravelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'd2b:schema:validate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Validate the database schema';

    public function fire( \Doctrine\ORM\EntityManagerInterface $d2em )
    {
        $validator = new SchemaValidator($d2em);
        $exit = 0;

        if( $this->option('skip-mapping') )
        {
            $this->comment( '[Mapping]  Skipped mapping check.' );
        }
        elseif( $errors = $validator->validateMapping() )
        {
            foreach( $errors as $className => $errorMessages )
            {
                $this->error( "[Mapping]  FAIL - The entity-class '" . $className . "' mapping is invalid:" );

                foreach( $errorMessages as $errorMessage ) {
                    $this->line( '* ' . $errorMessage );
                }

                $this->line();
            }

            $exit += 1;
        }
        else
        {
            $this->info( "[Mapping]  OK - The mapping files are correct." );
        }

        if( $this->option( 'skip-sync' ) )
        {
            $this->comment( "[Database] SKIPPED - The database was not checked for synchronicity." );
        }
        elseif( !$validator->schemaInSyncWithMetadata() )
        {
            $this->error( "[Database] FAIL - The database schema is not in sync with the current mapping file." );
            $exit += 2;
        }
        else
        {
            $this->info( "[Database] OK - The database schema is in sync with the mapping files." );
        }
    }

    protected function getOptions()
    {
        return [
            [ 'skip-mapping', false, InputOption::VALUE_NONE, 'Skip the mapping validation check' ],
            [ 'skip-sync',    false, InputOption::VALUE_NONE, 'Skip checking if the mapping is in sync with the database' ],
        ];
    }

}
