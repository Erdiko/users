<?php
/**
 * Mailgun Model
 *
 * @category    Erdiko
 * @package     users
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Leo Daidone, leo@arroyolabs.com
 */

namespace erdiko\users\models;


class Mailgun extends \Mailgun\Mailgun
{
  private $domain;

  public function __construct()
  {
    $config = \Erdiko::getConfig('application')['mailgun'];
    $env = $config['environment'];
    $apiKey = $config[$env]['key'];
    $apiEndpoint = $config[$env]['endpoint'];
    $apiVersion = $config[$env]['version'];
    $ssl = $config[$env]['ssl'];
    $this->domain = $config[$env]['domain'];
    parent::__construct($apiKey, $apiEndpoint, $apiVersion, $ssl);
  }

  protected function getDefaults()
  {
    return array(
      'from'    => "Arroyo Labs <info@arroyolabs.com>",
      'to'      => "Julian <diazjulian@gmail.com>",
      'cc'      => '',
      'bcc'     => '',
      'subject' => 'Arroyo Labs',
      'text'    => '',
      'html'    => ''
    );
  }

  public function sendMail($postData)
  {
    $data = array_filter(array_replace($this->getDefaults(),(array)$postData));
    return $this->post("$this->domain/messages", $data, array());
  }


  public function forgotPassword($email, $newpass){
      //$to = $email;
      $subject = "Arroyo Labs - Password Reset";
      $html = "<p>Credentials were cleared, to login please use the following password:<br><strong>{$newpass}</strong><br>Thanks.<br><\p>";

      try{
          $this->sendMail(compact("to", "subject", "html"));
      }catch (\Exception $e){
          throw new \Exception('Could not send email.');
      }
  }
}