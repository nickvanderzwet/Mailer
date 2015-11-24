<?php
namespace RonRademaker\Mailer\Test;

use Mockery;
use PHPUnit_Framework_TestCase;
use RonRademaker\Mailer\Mailer;
use Swift_Message;
use Twig_Environment;

/**
 * Unit test for the Mailer wrapper
 *
 * @author Ron Rademaker
 */
class MailerTest extends PHPUnit_Framework_TestCase
{
    /**
     * Test sending a mail without really building a Swift_Message
     */
    public function testMailWithoutSwiftMessage()
    {
        $received = [];
        $mailerMock = Mockery::mock('Swift_Mailer');
        $mailerMock->shouldReceive('send')->andReturnUsing(function (Swift_Message $message) use (&$received) {
                $received['from'] = $message->getFrom();
                $received['to'] = $message->getTo();
                $received['subject'] = $message->getSubject();
        });

        $template = Mockery::mock();
        $template->shouldReceive('renderBlock')->andReturnUsing(function ($block) {
            return $block;
        });

        $environment = \Mockery::mock(Twig_Environment::class);
        $environment->shouldReceive('loadTemplate')->andReturn($template);

        $mailer = new Mailer($environment, $mailerMock);
        $mailer->sendEmail(['example@example.org' => 'Example Example'], ['sender@example.org' => 'Example Sender'], 'Template');

        $this->assertEquals(['sender@example.org' => 'Example Sender'], $received['from']);
        $this->assertEquals(['example@example.org' => 'Example Example'], $received['to']);
        $this->assertEquals('subject', $received['subject']);
    }
}
