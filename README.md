**NB: You are advised to use this project from here on in: http://laraveldoctrine.org/ **

# Doctrine2Bridge for Laravel 5

Adds the power of Doctrine2 to Laraval 5 (including authentication and SQL query logging support).

For Laravel4, see [opensolutions/doctrine2bridge](https://github.com/opensolutions/doctrine2bridge)

Laravel's Eloquent ORM is nice for rapid development and the active model pattern. However there's little out
there that can beat Doctrine2 when you need a more full-featured ORM.

This is an integration of Doctrine 2.x to Laravel 5.x as a composer package. Doctrine's EntityManager instance is accessible through a facade named `D2EM` and the cache is directly available via `D2Cache`.

Metadata is currently obtained via the [XML driver](http://docs.doctrine-project.org/en/latest/reference/xml-mapping.html). It should be easy to add additional drivers to this.

Authentication support is also included via a `Auth/Doctrine2UserProvider` class. Documentation on integrating this with Laravel's own authentication system [can be found here](https://github.com/opensolutions/doctrine2bridge/wiki/Auth).

## Installation

Installation is the usual for Laravel packages. You can find a detailed worked version of [how to install and test in the wiki](https://github.com/opensolutions/doctrine2bridge-l5/wiki/Install-from-Scratch).

Insert the following in the packages (`require`) section of your composer.json file and run an update (`composer update`):

    "opensolutions/doctrine2bridge-l5": "2.4.*",

Generally speaking, we'll try and match our minor versions (2.4.x) with Doctrine's but you should always use the latest `x` version of this.

Add the service providers to your Laravel application in `app/config/app.php`. In the `'providers'` array add:

    'Doctrine2Bridge\Doctrine2CacheBridgeServiceProvider',
    'Doctrine2Bridge\Doctrine2BridgeServiceProvider',

You'll need to publish and edit the configuration files:

    ./artisan vendor:publish --provider "Doctrine2Bridge\Doctrine2CacheBridgeServiceProvider"
    ./artisan vendor:publish --provider "Doctrine2Bridge\Doctrine2BridgeServiceProvider"

This should get you a fresh copy of the configuration files (`d2bcache.php` and `db2doctrine.php`) in the configs directory.

Now, edit these as well as setting Laravel5's own `cache.php` and `database.php` appropriately.

The default directory for Doctrine2's xml schema is `database/xml`. This can be configured in `config/d2bdoctrine.php`.

Documentation on integrating this with Laravel's own authentication system [can be found here](https://github.com/opensolutions/doctrine2bridge-l5/wiki/Auth).

## Usage

Four bindings are created which can be injected via Laravel's IoC in the standard manner:

 * `Doctrine\ORM\EntityManagerInterface` (which is an instance of Doctrine\ORM\EntityManager)
 * `Doctrine\Common\Cache\Cache` (which is an instance of the appropriate cache provider)
 * `Doctrine\ORM\Mapping\ClassMetadataFactory` (used in this package by the console generator commands)
 * `Doctrine2Bridge\Support\Repository` (used by the `D2R` facade, see below)

Three facades are provided - for the Doctrine2 cache, the entity manager and a convenience repository generator. These can be used as follows:

    D2Cache::save( $key, $value );
    D2Cache::fetch( $key );

    D2EM::persist( $object );
    D2EM::flush();
    $users = D2EM::getRepository( 'Entities\User' )->findAll();

Typically we'd create and use a repository as follows:

    $sample = D2EM::getRepository( '\Entities\SampleEntity' )->find(5);

Assuming `d2bdoctrine.namespaces.models => 'Entities'`, then we can use the `D2R` facade in any of the following ways to achieve the same result:

    $sample = D2R::r( 'SampleEntity' )->find(5);
    $sample = D2R::r( 'Entities\SampleEntity' )->find(5);
    $sample = D2R::r( 'SampleEntity', 'Entities' )->find(5);

## More Detailed Usage

The configuration file by default expects to find XML schema definitions under `database/xml`. Let's say for example we have a single schema file called `database/xml/Entities.SampleEntity.dcm.xml` containing:

    <?xml version="1.0"?>
    <doctrine-mapping xmlns="http://doctrine-project.org/schemas/orm/doctrine-mapping" xsi="http://www.w3.org/2001/XMLSchema-instance" schemaLocation="http://doctrine-project.org/schemas/orm/doctrine-mapping.xsd">
        <entity name="Entities\SampleEntity" repository-class="Repositories\Sample">
            <id name="id" type="integer">
                <generator strategy="AUTO"/>
            </id>
            <field name="name" type="string" length="255" nullable="true"/>
        </entity>
    </doctrine-mapping>

Assuming you've configured your database connection parameters in the config file and you're positioned in the base directory of your project, we can create the entities, proxies and repositories with:

    ./artisan d2b:generate:entities
    ./artisan d2b:generate:proxies
    ./artisan d2b:generate:repositories

There is also a handy shortcut for this:

    ./artisan d2b:generate:all

Read the output of these commands as they may need to be run twice.

We also bundle a full Doctrine2 CLI utilty so the above could also be done via:

    ./vendor/bin/d2b-doctrine2 orm:generate-entities database/
    ./vendor/bin/d2b-doctrine2 orm:generate-proxies
    ./vendor/bin/d2b-doctrine2 orm:generate-repositories database/

You can also (drop) and create the database with:

    ./artisan d2b:schema:drop --commit
    ./artisan d2b:schema:create --commit

And you can update and validate via:

    ./artisan d2b:schema:update --commit
    ./artisan d2b:schema:validate


Now you can add some data to the database:

    $se = new Entities\SampleEntity;
    $se->setName( rand( 0, 100 ) );
    D2EM::persist( $se );
    D2EM::flush();

And query it:

    echo count( D2EM::getRepository( 'Entities\SampleEntity' )->findAll() );

I use the excellent [Skipper](http://www.skipper18.com/) to create and manage my XML schema files.

## Convenience Function for Repositories


## SQL Query Logging

This package includes an implementation of `Doctrine\DBAL\Logging\SQLLlogger` which times the queries and calls the Laravel [Log](http://laravel.com/docs/errors#logging) facade to log the query execution times and the SQL queries.

This logger can be enabled in the configuration file.

## License

Like the Laravel framework itself, this project is open-sourced under the [MIT license](http://opensource.org/licenses/MIT).

## Inspiration

Based on my original package [opensolutions/doctrine2bridge](https://github.com/opensolutions/doctrine2bridge) for Laravel4. Some additional inspiration when porting to Laravel5 from [mitchellvanw/laravel-doctrine](https://github.com/mitchellvanw/laravel-doctrine).

