<?php
/**
 * Mailgun Model
 *
 * @category    Erdiko
 * @package     users
 * @copyright   Copyright (c) 2016, Arroyo Labs, http://www.arroyolabs.com
 * @author      Julian Diaz, julian@arroyolabs.com
 */

namespace erdiko\users\models;

class Mailgun extends \Mailgun\Mailgun
{
  private $domain;

  public function __construct()
  {
    $config = \Erdiko::getConfigFile(dirname(__DIR__)."/shared/mailgun.json");
    $env = $config['environment'];
    $apiKey = $config[$env]['key'];
    $apiEndpoint = $config[$env]['endpoint'];
    $apiVersion = $config[$env]['version'];
    $ssl = $config[$env]['ssl'];
    $this->domain = $config[$env]['domain'];

    $httpClient =  new \GuzzleHttp\Client();
    $adapter = new \Http\Adapter\Guzzle6\Client($httpClient);

    parent::__construct($apiKey, $adapter, $apiEndpoint);
  }

  protected function getDefaults()
  {
    return array(
      'from'    => "Arroyo Labs <info@arroyolabs.com>",
      'to'      => "Arroyo Labs <info@arroyolabs.com>",
      'cc'      => '',
      'bcc'     => '',
      'subject' => 'Arroyo Labs',
      'text'    => '',
      'html'    => ''
    );
  }

    /**
     * @param $postData
     * @return \stdClass
     * Send a email with specific data
     */
  public function sendMail($postData)
  {
    $data = array_filter(array_replace($this->getDefaults(),(array)$postData));
    return $this->sendMessage($this->domain, $data);
  }

    /**
     * @param $email
     * @param $html
     * @return \stdClass
     * @throws \Exception
     *
     * Send a email with a new password using a html view
     */
  public function forgotPassword($email, $html)
  {
      $to = $email;
      $subject = "Arroyo Labs - Password Reset";

      try{
          return $this->sendMail(compact("to", "subject", "html"));
      }catch (\Exception $e){
          throw new \Exception('Could not send email.');
      }
  }
}