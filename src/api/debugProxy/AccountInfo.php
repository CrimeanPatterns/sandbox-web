<?php

class AccountInfo
{

  /**
   * 
   * @var int $AccountID
   * @access public
   */
  public $AccountID;

  /**
   * 
   * @param int $AccountID
   * @access public
   */
  public function __construct($AccountID)
  {
    $this->AccountID = $AccountID;
  }

}
