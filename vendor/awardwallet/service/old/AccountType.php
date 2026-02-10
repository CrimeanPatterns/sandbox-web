<?php

class AccountType
{

  /**
   * @var string $ProviderCode
   * @access public
   */
  public $ProviderCode;

  /**
   * @var string $DisplayName
   * @access public
   */
  public $DisplayName;

  /**
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * @var string $Login2
   * @access public
   */
  public $Login2;

  /**
   * @var string $Password
   * @access public
   */
  public $Password;

  /**
   * @var string $Balance
   * @access public
   */
  public $Balance;

  /**
   * @var string $ExpirationDate
   * @access public
   */
  public $ExpirationDate;

  /**
   * @var string $ErrorCode
   * @access public
   */
  public $ErrorCode;

  /**
   * @var string $ErrorMessage
   * @access public
   */
  public $ErrorMessage;

  /**
   * @param string $ProviderCode
   * @param string $DisplayName
   * @param string $Login
   * @param string $Login2
   * @param string $Password
   * @param string $Balance
   * @param string $ExpirationDate
   * @param string $ErrorCode
   * @param string $ErrorMessage
   * @access public
   */
  public function __construct($ProviderCode, $DisplayName, $Login, $Login2, $Password, $Balance, $ExpirationDate, $ErrorCode, $ErrorMessage)
  {
    $this->ProviderCode = $ProviderCode;
    $this->DisplayName = $DisplayName;
    $this->Login = $Login;
    $this->Login2 = $Login2;
    $this->Password = $Password;
    $this->Balance = $Balance;
    $this->ExpirationDate = $ExpirationDate;
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
  }

}
