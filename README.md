# Mailer
Wrapper around Swiftmail.

# Usage
The mailer wrapper can be used without any knowledge of Swift Mailer, or by using the full power by creating your own Swift_Message. 

## Installation
```
composer require ronrademaker/mailer
```

## Configuration
Assuming you'll want to register the mailer in a DIC, Symfony XML example:

```
<?xml version="1.0" encoding="UTF-8"?>
<!--
Services file that should be included in any DHD project
-->
<container xmlns='http://symfony.com/schema/dic/services'
  xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'
  xsi:schemaLocation='http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd'>

  <parameters>
    <parameter key='mailer_twig.path'>[LOCATION OF YOUR TEMPLATES]</parameter>
    <parameter key='mailer_twig.options' type='collection'> 
      [ADD YOUR OPTIONS]
    </parameter>
  </parameters>

  <services>
    <service id='mailer_transport' class='Swift_Transport'>
      <factory class='Swift_SendmailTransport' method='newInstance'/>
    </service>
    <service id='mailer_swift' class='Swift_Mailer'>
      <factory class='Swift_Mailer' method='newInstance'/>
      <argument type='service' id='mailer_transport'/>
    </service>
    <service id='mailer_twig_loader' class='Twig_Loader_Filesystem'>
      <argument>%mailer_twig.path%</argument>
    </service>
    <service id='mailer_twig' class='Twig_Environment'>
      <argument type='service' id='mailer_twig_loader'/>
      <argument>%mailer_twig.options%</argument>
    </service>
    <service id='mailer' class='RonRademaker\Mailer\Mailer'>
      <argument type='service' id='mailer_twig'/>
      <argument type='service' id='mailer_swift'/>
    </service>
  </services>
</container>

```

## Sending mails with twig template

### Example template
```
{% block subject 'My Mail Subject' %}

{% block body_html %}
  <p>Some mail with HTML formatting</p>
{% endblock %}

{% block body_text %}
  Some mail in boring plain text
{% endblock %}
```

### Send the mail
```
$mailer->sendEmail(['example@example.org' => 'Example Receiver'], 'sender@example.org' => 'Example Sender', 'my_twig_template');
```

## Send a Swift Message
Create your message in $message and 
```
$mailer->sendMessage($message);
```
