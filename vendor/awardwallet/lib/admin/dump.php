<?
ob_start();
var_dump($_GET);
var_dump($_POST);
var_dump($_COOKIE);
echo base64_encode(ob_get_clean());