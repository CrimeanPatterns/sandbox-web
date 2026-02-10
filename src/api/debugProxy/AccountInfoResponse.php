<?php

class AccountInfoResponse
{

  /**
   * 
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * 
   * @var string $Login2
   * @access public
   */
  public $Login2;

  /**
   * 
   * @var string $Login3
   * @access public
   */
  public $Login3;

  /**
   *
   * @var string $Pass
   * @access public
   */
  public $Pass;

  /**
   * 
   * @var int $ProviderID
   * @access public
   */
  public $ProviderID;

  /**
   * 
   * @var string $DisplayName
   * @access public
   */
  public $DisplayName;

  /**
   * 
   * @var string $ProviderCode
   * @access public
   */
  public $ProviderCode;

  /**
   * 
   * @var int $ProviderEngine
   * @access public
   */
  public $ProviderEngine;

  /**
   * 
   * @var string $BrowserState
   * @access public
   */
  public $BrowserState;

  /**
   * 
   * @var string $Answers
   * @access public
   */
  public $Answers;

  /**
   * 
   * @param string $Login
   * @param string $Login2
   * @param string $Login3
   * @param int $ProviderID
   * @param string $DisplayName
   * @param string $ProviderCode
   * @param int $ProviderEngine
   * @param string $BrowserState
   * @param string $Answers
   * @access public
   */
  public function __construct($Login, $Login2, $Login3, $Pass, $ProviderID, $DisplayName, $ProviderCode, $ProviderEngine, $BrowserState, $Answers)
  {
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Login3 = $Login3;
    $this->Pass = $Pass;
    $this->ProviderID = $ProviderID;
    $this->DisplayName = $DisplayName;
    $this->ProviderCode = $ProviderCode;
    $this->ProviderEngine = $ProviderEngine;
    $this->BrowserState = $BrowserState;
    $this->Answers = $Answers;
  }

}
