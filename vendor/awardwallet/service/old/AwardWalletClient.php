<?php

include_once('ListAccountsResponse.php');
include_once('AccountType.php');
include_once('ListAccountsRequest.php');

class AwardWalletClient extends SoapClient
{

  /**
   * @var array $classmap The defined classes
   * @access private
   */
  private static $classmap = array(
    'ListAccountsResponse' => 'ListAccountsResponse',
    'AccountType' => 'AccountType',
    'ListAccountsRequest' => 'ListAccountsRequest');

  /**
   * @param string $wsdl The wsdl file to use
   * @param array $config A array of config values
   * @access public
   */
  public function __construct($wsdl = 'http://awardwallet.local/api/client.wsdl', $options = array())
  {
    foreach(self::$classmap as $key => $value)
    {
      if(!isset($options['classmap'][$key]))
      {
        $options['classmap'][$key] = $value;
      }
    }
    
    parent::__construct($wsdl, $options);
  }

  /**
   * @param ListAccountsRequest $body
   * @access public
   */
  public function ListAccounts(ListAccountsRequest $body)
  {
    return $this->__soapCall('ListAccounts', array($body));
  }

}
