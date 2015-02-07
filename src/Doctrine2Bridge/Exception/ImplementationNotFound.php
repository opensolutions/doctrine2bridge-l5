<?php

namespace Doctrine2Bridge\Exception;

/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class ImplementationNotFound extends \Exception {

    public function __construct( $message = null, $code = 0, Exception $previous = null )
    {
        return parent::__construct(
            "No class / implementation found for {$message}", $code, $previous
        );
    }

}
