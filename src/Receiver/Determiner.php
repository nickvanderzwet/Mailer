<?php

namespace RonRademaker\Mailer\Receiver;

use WMFunctionLibrary;
use WMMongoDBRef;

/**
 * The determiner class is used to determine the receivers of a confirmation.
 *
 * @author  Jaap Romijn
 **/
class Determiner implements DeterminerInterface
{
    /**
     * An array holding known camelCase results.
     *
     * @var array
     */
    private $camelCaseCache = array();

    /**
     * The determiners to retrieve receivers.
     *
     * @var array<DeterminerInterface>
     **/
    protected $determiners = array();

    /**
     * Returns the internal determiners.
     *
     * @param array $determiners<DeterminerInterface>
     **/
    protected function getDeterminers()
    {
        return $this->determiners;
    }

    /**
     * Determiners to confirm for.
     *
     * @param array $determiners<DeterminerInterface>
     *
     * @return Determiner
     **/
    public function setDeterminers(array $determiners)
    {
        $this->determiners = $determiners;

        return $this;
    }

    /**
     * Adds a determiner to the collection of determiners, unless it is already set or adding would cause an endless loop.
     *
     * @param DeterminerInterface $determiner
     *
     * @return Determiner
     **/
    public function addDeterminer(DeterminerInterface $determiner)
    {
        if (in_array($determiner, $this->getDeterminers())) {
            array_unshift($this->determiners, $determiner);
        }

        return $this;
    }

    /**
     * Returns true if this determiner is a handler for the given object and event.
     *
     * @param stdClass $object
     * @param string   $event
     *
     * @return bool
     **/
    protected function isHandler($object, $event)
    {
        return true;
    }

    /**
     * Returns the handler for the given object and event.
     *
     * @param stdClass $object
     * @param string   $event
     *
     * @return DeterminerInterface|null
     **/
    public function getHandler($object, $event)
    {
        foreach ($this->getDeterminers() as $internalDeterminer) {
            if (($determiner = $internalDeterminer->getHandler($object, $event)) instanceof DeterminerInterface) {
                return determiner;
            }
        }

        if ($this->isHandler($object, $event)) {
            return $this;
        }
    }

    /**
     * Returns a collection of receivers for the given object and event.
     *
     * @param stdClass $object
     * @param string   $event
     * @param array    $receivers
     *
     * @return array
     **/
    public function getReceivers($object, $event, array $receivers = array())
    {
        if (($determiner = $this->getHandler($object, $event)) instanceof DeterminerInterface) {
            if ($determiner !== $this) {
                return $determiner->getReceivers($object, $event, $receivers);
            }
        }

        return $this->parseReceivers($object, $receivers);
    }

    /**
     * Parses the receivers on the object if needed.
     *
     * @param stdClass $object
     * @param array    $receivers
     *
     * @return array
     **/
    protected function parseReceivers($object, array $receivers)
    {
        $parsedReceivers = array();
        foreach ($receivers as $email => $name) {
            $parsedReceivers[ $this->parseReceiverValue($object, $email) ] = $this->parseReceiverValue($object, $name);
        }

        return array_filter($parsedReceivers);
    }

    /**
     * Parses the receivers on the object if needed.
     *
     * @param stdClass $object
     * @param string   $value
     *
     * @return array
     **/
    protected function parseReceiverValue($object, $value)
    {
        if (preg_match('/^{{.*}}$/', $value)) {
            $chainValue = $object;
            foreach (array_map(function ($v) { return 'get'.$this->camelCase($v); }, explode('.', str_replace(array('}', '{'), '', $value))) as $getter) {
                if (is_object($chainValue)) {
                    $chainValue = $this->retrievePossibleObjectValue($chainValue, $getter);
                }
            }

            return $chainValue;
        }

        return $value;
    }

    /**
     * Retrieves a value from an object, but sometimes the getter refers to an object and other functions are needed to retreive that actual object.
     * For instance a getter of a Mongo object will return the reference instead of the actual object. Tulip requires a special getInstance getter.
     * This function can be used to bridge these disparities.
     *
     *
     * @param stdClass $object
     * @param string   $getter
     *
     * @return mixed
     **/
    protected function retrievePossibleObjectValue($object, $getter)
    {
        $object = $object->$getter();
        if (class_exists('WMMongoDBRef') && WMMongoDBRef::isRef($object)) {
            return WMMongoDBRef::getObject(null, $object);
        }

        return $object;
    }

    /**
     * camelCase.
     *
     * camelCase function (This_is_a_string -> ThisIsAString; this_is_a_string -> ThisIsAString)
     *
     * @since Fri Nov 17 2006
     *
     * @param string $string
     *
     * @return string
     */
    public function camelCase($string)
    {
        if (array_key_exists($string, $this->camelCaseCache)) {
            return $this->camelCaseCache[$string];
        }

        $result = '';
        foreach (explode('_', $string) as $substring) {
            $result .= ucfirst($substring);
        }

        $this->camelCaseCache[$string] = $result;

        return $result;
    }
}
