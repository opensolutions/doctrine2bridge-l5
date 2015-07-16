<?php

// Source: https://gist.github.com/bubba-h57/3714787

namespace Doctrine2Bridge\Utils;

class JsonSerializor {

    /**
     * Converts the Doctrine Entity into a JSON Representation
     *
     * @param object $object The Object (Typically a Doctrine Entity) to convert to an array
     * @param integer $depth The Depth of the object graph to pursue
     * @param array $whitelist List of entity=>array(parameters) to convert
     * @param array $blacklist List of entity=>array(parameters) to skip
     * @return string
     */
    public static function json_encode($object, $depth=1, $whitelist=array(), $blacklist=array()){
        return json_encode(self::toArray($object, $depth, $whitelist, $blacklist));
    }

    /**
     * Serializes our Doctrine Entities
     *
     * This is the primary entry point, because it assists with handling collections
     * as the primary Object
     *
     * @param object $object The Object (Typically a Doctrine Entity) to convert to an array
     * @param integer $depth The Depth of the object graph to pursue
     * @param array $whitelist List of entity=>array(parameters) to convert
     * @param array $blacklist List of entity=>array(parameters) to skip
     * @return NULL|Array
     *
     */
    public static function toArray($object, $depth = 1,$whitelist=array(), $blacklist=array()){

        // If we drop below depth 0, just return NULL
        if ($depth < 0){
            return NULL;
        }

        // If this is an array, we need to loop through the values
        if (is_array($object)){
            // Somthing to Hold Return Values
            $anArray = array();

            // The Loop
            foreach ($object as $value){
                // Store the results
                $anArray[] = self::arrayizor($value, $depth, $whitelist, $blacklist);
            }
            // Return it
            return $anArray;
        }else{
            // Just return it
            return self::arrayizor($object, $depth, $whitelist, $blacklist);
        }
    }

    /**
     * This does all the heavy lifting of actually converting to an array
     *
     * @param object $object The Object (Typically a Doctrine Entity) to convert to an array
     * @param integer $depth The Depth of the object graph to pursue
     * @param array $whitelist List of entity=>array(parameters) to convert
     * @param array $blacklist List of entity=>array(parameters) to skip
     * @return NULL|Array
     */
    private static function arrayizor($anObject, $depth, $whitelist=array(), $blacklist=array()){
        // Determine the next depth to use
        $nextDepth = $depth - 1;

        // Lets get our Class Name
        // @TODO: Making some assumptions that only objects get passed in, need error checking
        $clazzName = get_class($anObject);

        // Now get our reflection class for this class name
        $reflectionClass = new \ReflectionClass($clazzName);

        // Then grap the class properites
        $clazzProps = $reflectionClass->getProperties();

        if (is_a($anObject, 'Doctrine\ORM\Proxy\Proxy')){
            $parent = $reflectionClass->getParentClass();
            $clazzName = $parent->getName();
            $clazzProps = $parent->getProperties();
        }
        // A new array to hold things for us
        $anArray = array();

        // Lets loop through those class properties now
        foreach ($clazzProps as $prop){

            // If a Whitelist exists
            if (@count($whitelist[$clazzName]) > 0){
                // And this class property is not in it
                if (! @in_array($prop->name, $whitelist[$clazzName])){
                    // lets skip it.
                    continue;
                }
            // Otherwise, if a blacklist exists
            }elseif (@count($blacklist[$clazzName] > 0)){
                // And this class property is in it
                if (@in_array($prop->name, $blacklist[$clazzName])){
                    // lets skip it.
                    continue;
                }
            }

            // We know the property, lets craft a getProperty method
            // $method_name = 'get' . ucfirst($prop->name) ;
            $method_name = 'get' . preg_replace_callback(
                '/_[a-zA-Z]/',
                function ($match) {
                    $s = $match[0];
                    return strtoupper($s[1]);
                },
                ucfirst( $prop->name )
            );

            // And check to see that it exists for this object
            if (! method_exists($anObject, $method_name)){
                continue;
            }
            // It did, so lets call it!
            $aValue = $anObject->$method_name();

            // If it is an object, we need to handle that
            if (is_object($aValue)){
                // If it is a datetime, lets make it a string
                if (get_class($aValue) === 'DateTime'){
                    $anArray[$prop->name] = $aValue->format('Y-m-d H:i:s');

                // If it is a Doctrine Collection, we need to loop through it
                }elseif(get_class($aValue) ==='Doctrine\ORM\PersistentCollection'){
                    $collect = array();
                    foreach ($aValue as $val){
                        $collect[] = self::toArray($val, $nextDepth, $whitelist, $blacklist);
                    }
                    $anArray[$prop->name] = $collect;

                // Otherwise, we can simply make it an array
                }else{
                    $anArray[$prop->name] = self::toArray($aValue, $nextDepth, $whitelist, $blacklist);
                }
            // Otherwise, we just use the base value
            }else{

                $anArray[$prop->name] = $val;
            }
        }
        // All done, send it back!
        return $anArray;
    }
}
