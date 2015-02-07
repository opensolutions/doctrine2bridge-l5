<?php

namespace Doctrine2Bridge\Support\Facades;

use Illuminate\Support\Facades\Facade;


/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class Doctrine2Repository extends Facade {

        /**
         * Get the registered name of the component.
         *
         * @return string
         */
        protected static function getFacadeAccessor() { return '\Doctrine2Bridge\Support\Repository'; }

}
