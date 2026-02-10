<?php
require('../../kernel/public.php');
require_once 'DebugProxyClient.php';
require_once 'DebugProxyService.php';

ini_set("soap.wsdl_cache_enabled", "0");

$server = new SoapServer('debugProxy.wsdl', array('classmap' => array(
		'AccountInfo' => 'AccountInfo',
		'DebugProxyRequest' => 'DebugProxyRequest',
)));
$service = new DebugProxyService();
$server->setObject($service);
if(isset($_GET['wsdl']) && ($_SERVER['REQUEST_METHOD'] == 'GET'))
	$server->handle();
else{
	if (!$service->Authenticate()){
		$server->fault("AccessDenied", "Check your UserName and Password");
		exit();
	}
	$server->handle();
}
?>