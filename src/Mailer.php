<?php
namespace RonRademaker\Mailer;

use Swift_Message;
use Twig_Environment;

/**
 * Mailer service wrapper around Swiftmailer using Twig
 *
 * @author Ron Rademaker
 */
class Mailer
{
    /**
     * Template for the mail
     *
     * @var Twig_TemplateInterface
     */
    private $template;

    /**
     * Message being constructed
     *
     * @var Swift_Message
     */
    private $message;

    /**
     * Twig environment
     *
     * @var Twig_Environment
     */
    private $environment;

    /**
     * The mailer to use
     *
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * Creates a Mailer for $environment
     *
     * @param Twig_Environment $environment
     */
    public function __construct(Twig_Environment $environment, Swift_Mailer $mailer)
    {
        $this->environment = $environment;
        $this->mailer = $mailer;
    }

    /**
     * Sends an email
     *
     * @param array $receivers
     * @param array from
     * @param string $template - twig template name
     * @param array $arguments - will be available in the template
     */
    public function sendEmail(array $receivers, $from, $template, array $arguments = array())
    {
        $this->template = $this->environment->loadTemplate($template);
        $this->message = Swift_Message::newInstance();
        $this->setSender($from);
        $this->setReceivers($receivers);
        $this->setSubjectFromArguments($arguments);
        $this->setBodyFromArguments($arguments);
        $this->send();
    }

    /**
     * Sends a Swift_Message
     *
     * @param Swift_Message $message
     */
    public function sendMessage(Swift_Message $message)
    {
        $this->message = $message;
        $this->send();
    }

    /**
     * Sends the mail
     */
    public function send()
    {
        $this->mailer->send($this->message);
    }

    /**
     * Sets the receivers for the curent email
     *
     * @param array $receivers
     */
    private function setReceivers(array $receivers)
    {
        $this->message->setTo($receivers);
    }

    /**
     * Sets the sender for the curent email
     *
     * @param array $sender
     */
    private function setSender(array $sender)
    {
        $this->message->setFrom($sender);
    }

    /**
     * Sets the subject from the template

     * @param array $arguments
     */
    private function setSubjectFromArguments(array $arguments)
    {
        $this->message->setSubject($this->template->renderBlock('subject', $arguments));
    }

    /**
     * Sets the body, both text and html, from the template
     *
     * @param array $arguments
     */
    private function setBodyFromArguments(array $arguments)
    {
        $this->message->setBody($this->template->renderBlock('body_text', $arguments), 'text/plain');
        $this->message->addPart($this->template->renderBlock('body_html', $arguments), 'text/html');
    }



}
