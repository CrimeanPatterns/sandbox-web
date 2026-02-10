<?
session_start();

echo "id: ".session_id()."<br/>";
echo "<pre>";
var_export($_SESSION);
echo "</pre>";