<?php

namespace RonRademaker\Mailer\Test;

use PHPUnit_Framework_TestCase;
use RonRademaker\Mailer\Receiver\Determiner;
use RonRademaker\Mailer\Receiver\DeterminerInterface;
use stdClass;

/**
 * Unit test for the receiver determiner.
 *
 * @author Jaap Romijn <jaap@connectholland.nl>
 */
class DeterminerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test sending a mail without really building a Swift_Message.
     */
    public function testPlainTextReceiver()
    {
        $object = new DeterminerTestObject([
            'email' => 'foo@example.com',
            'fullName' => 'Foo Barz',
            'object' => new DeterminerTestObject([
                'email' => 'foo2@example.com',
                'fullName' => 'Foo2 Barz',
                'object' => new DeterminerTestObject([
                    'email' => 'foo3@example.com',
                    'fullName' => 'Foo3 Barz',
                ]),
            ]),
        ]);

        $receiverInput = [
            'email' => 'fooY@example.com',
            'name' => 'FooY Barz',
        ];
        $receiver = (new Determiner())->getReceivers($object, null, $receiverInput);
        $this->assertEquals($receiverInput['email'], $receiver['email'], sprintf("Asserting that the receiver (%s) is unchanged when using string email configuration:\n <email name='%s' email='%s'\\>\n", $receiverInput['email'], $receiverInput['email'], $receiverInput['name']));
        $this->assertEquals($receiverInput['name'], $receiver['name'], sprintf("Asserting that the receiver (%s) is unchanged when using string name configuration:\n <email name='%s' email='%s'\\>\n", $receiverInput['name'], $receiverInput['email'], $receiverInput['name']));
    }

    /**
     * Tests that the configuration for the e-mail name and e-mail address are retrieved from the object.
     */
    public function testVariableTextReceiver()
    {
        $object = new DeterminerTestObject([
            'email' => 'foo@example.com',
            'fullName' => 'Foo Barz',
            'object' => new DeterminerTestObject([
                'email' => 'foo2@example.com',
                'fullName' => 'Foo2 Barz',
                'object' => new DeterminerTestObject([
                    'email' => 'foo3@example.com',
                    'fullName' => 'Foo3 Barz',
                ]),
            ]),
        ]);

        $receiverInput = [
            'email' => '{{object.object.email}}',
            'name' => '{{object.object.full_name}}',
        ];

        $receiver = (new Determiner())->getReceivers($object, null, $receiverInput);
        $expected = 'foo3@example.com';
        $this->assertEquals($expected, $receiver['email'], sprintf("Asserting that the receiver email (%s) is retrieved from object when using email configuration:\n <email name='%s' email='%s'\\>\n", $receiverInput['email'], $receiverInput['email'], $receiverInput['name']));
        $expected = 'Foo3 Barz';
        $this->assertEquals($expected, $receiver['name'], sprintf("Asserting that the receiver name (%s) is retrieved from object when using email configuration:\n <email name='%s' email='%s'\\>\n", $receiverInput['name'], $receiverInput['email'], $receiverInput['name']));
    }

    /**
     * Tests that the configuration for the e-mail name and e-mail address are retrieved from the object in the 'email => name' format that Swift mailer expects.
     */
    public function testVariableTextReceiverWithoutArrayKeys()
    {
        $object = new DeterminerTestObject([
            'email' => 'foo@example.com',
            'fullName' => 'Foo Barz',
            'object' => new DeterminerTestObject([
                'email' => 'foo2@example.com',
                'fullName' => 'Foo2 Barz',
                'object' => new DeterminerTestObject([
                    'email' => 'foo3@example.com',
                    'fullName' => 'Foo3 Barz',
                ]),
            ]),
        ]);

        $receiverInput = [
            '{{object.object.email}}' => '{{object.object.full_name}}',
        ];

        $receiver = (new Determiner())->getReceivers($object, null, $receiverInput);
        $this->assertEquals(1, count($receiver), sprintf("Asserting that 1 receiver was parsed out input:\n%s", var_export($receiverInput, true)));
        foreach ($receiver as $email => $name) {
            $expected = 'foo3@example.com';
            $this->assertEquals($expected, $email, sprintf("Asserting that the receiver email (%s) is retrieved from object when using email configuration:\n <email name='%s' email='%s'\\>\n", '{{object.object.email}}', '{{object.object.email}}', '{{object.object.name}}'));
            $expected = 'Foo3 Barz';
            $this->assertEquals($expected, $name, sprintf("Asserting that the receiver name (%s) is retrieved from object when using email configuration:\n <email name='%s' email='%s'\\>\n", '{{object.object.name}}', '{{object.object.email}}', '{{object.object.name}}'));
        }
    }

    /**
     * Tests that that the handler retrieved through Determiner::getHandler is of instance DeterminerInterface.
     */
    public function testGetHandler()
    {
        $object = new DeterminerTestObject();
        $expected = DeterminerInterface::class;
        $this->assertInstanceOf($expected, (new Determiner())->getHandler($object, null), sprintf('Asserting that the handler retrieved through Determiner::getHandler is of instance %s.', $expected));
    }
}

class DeterminerTestObject
{
    /**
     * Creates a new DeterminerTestObject.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $property => $value) {
            $this->{$property} = $value;
        }
    }

    /**
     * Magic property getter.
     *
     * @param string $property
     * @param mixed  $arguments
     */
    public function __call($property, $arguments)
    {
        $property = lcfirst(str_replace('get', '', $property));
        if (property_exists($this, $property)) {
            return $this->$property;
        }
    }
}
