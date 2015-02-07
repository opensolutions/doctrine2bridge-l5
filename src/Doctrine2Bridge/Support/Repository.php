<?php

namespace Doctrine2Bridge\Support;

use Config;

/**
 * Doctrine2 Bridge - Brings Doctrine2 to Laravel 5.
 *
 * A class to provide a facade for generating repository objects
 *
 * @author Barry O'Donovan <barry@opensolutions.ie>
 * @copyright Copyright (c) 2015 Open Source Solutions Limited
 * @license MIT
 */
class Repository {

    /**
     * The entity manager
     */
    private $d2em = null;

    public function __construct( \Doctrine\ORM\EntityManagerInterface $d2em )
    {
        $this->d2em = $d2em;
    }

    public function r( $repository, $namespace = null )
    {
        if( $namespace == null ) {
            if( strpos( $repository, '\\' ) === false )
                $repository = Config::get( 'd2bdoctrine.namespaces.models' ) . '\\' . $repository;
        } else {
            $repository = $namespace . '\\' . $repository;
        }

        return $this->d2em->getRepository( $repository );
    }
}
