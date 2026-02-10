<?php

class ListAccountsRequest
{

  /**
   * @var string $Login
   * @access public
   */
  public $Login;

  /**
   * @var string $Password
   * @access public
   */
  public $Password;

  /**
   * @param string $Login
   * @param string $Password
   * @access public
   */
  public function __construct($Login, $Password)
  {
    $this->Login = $Login;
    $this->Password = $Password;
  }

}
