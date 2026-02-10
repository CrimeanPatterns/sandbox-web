<?php

include_once('AccountInfo.php');
include_once('AccountInfoResponse.php');
include_once('DebugProxyRequest.php');
include_once('DebugProxyResponse.php');


/**
 * Client debug proxy interface
 * 
 */
class DebugProxyClient extends TExtSoapClient {

  const UNKNOWN_PASSWORD = 'ACCOUNT_PASSWORD';

  /**
   * 
   * @var array $classmap The defined classes
   * @access private
   */
  private static $classmap = array(
    'AccountInfo' => 'AccountInfo',
    'Request' => 'Request',
    'AccountInfoResponse' => 'AccountInfoResponse',
    'DebugProxyRequest' => 'DebugProxyRequest',
    'DebugProxyResponse' => 'DebugProxyResponse',
  );

  /**
   * 
   * @param array $config A array of config values
   * @param string $wsdl The wsdl file to use
   * @access public
   */
  public function __construct(array $options = array(), $wsdl = 'debugProxy.wsdl')
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
   * 
   * @param AccountInfo $body
   * @access public
   * @return AccountInfoResponse
   */
  public function GetAccountInfo(AccountInfo $body)
  {
    return $this->__soapCall('GetAccountInfo', array($body));
  }

  /**
   *
   * @param DebugProxyRequest $body
   * @access public
   * @returns DebugProxyResponse
   */
  public function SendHttpRequest(DebugProxyRequest $body)
  {
    return $this->__soapCall('SendHttpRequest', array($body));
  }

}
