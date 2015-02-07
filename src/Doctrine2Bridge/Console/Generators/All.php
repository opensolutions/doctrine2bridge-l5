<?php

namespace Doctrine2Bridge\Console\Generators;


use Illuminate\Console\Command as LaravelCommand;

use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;


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
class All extends LaravelCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'd2b:generate:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate all Doctrine2 entities, proxies and repositoies';

    public function fire()
    {
        $this->call( 'd2b:generate:entities' );
        $this->call( 'd2b:generate:proxies' );
        $this->call( 'd2b:generate:repositories' );
    }

}
