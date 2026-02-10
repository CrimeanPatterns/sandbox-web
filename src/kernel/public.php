<?php
set_time_limit(120);

error_reporting(E_ALL ^ E_DEPRECATED);

require_once __DIR__ . '/../../app/kernel.php';

global $sPath;
$sPath = realpath(__DIR__ . '/..');

$Connection = new PdoConnection();
$Connection->Open(["Host" => "mysql", "Login" => "awardwallet", "Password" => "awardwallet", "Database" => "awardwallet"]);

