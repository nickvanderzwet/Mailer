<?php

namespace RonRademaker\Mailer\Receiver;

/**
 * The determiner interface describes the interface used to determine the receivers of a confirmation.
 *
 * @author  Jaap Romijn
 **/
interface DeterminerInterface
{
    /**
     * Returns a collection of receivers for the given object and event.
     *
     * @param stdClass $object
     * @param string   $event
     * @param array    $receivers
     *
     * @return array
     **/
    public function getReceivers($object, $event, array $receivers = array());

    /**
     * Returns the handler for the given object and event.
     *
     * @param stdClass $object
     * @param string   $event
     *
     * @return DeterminerInterface
     **/
    public function getHandler($object, $event);
}
