<?php

class ListAccountsResponse
{

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
   * @var AccountType $Accounts
   * @access public
   */
  public $Accounts;

  /**
   * @param string $ErrorCode
   * @param string $ErrorMessage
   * @param AccountType $Accounts
   * @access public
   */
  public function __construct($ErrorCode, $ErrorMessage, $Accounts)
  {
    $this->ErrorCode = $ErrorCode;
    $this->ErrorMessage = $ErrorMessage;
    $this->Accounts = $Accounts;
  }

}
