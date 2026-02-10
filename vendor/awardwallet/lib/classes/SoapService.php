<?
class SoapService{

	public $UserName = null;
	public $Password = null;
	public $LogFile = "requests.log";

	function Log($s){
		$f = fopen("/var/log/www/wsdlawardwallet/".$this->LogFile, "a");
		fwrite($f, date('Y-m-d H:i:s')." ".$s."\n");
		fclose($f);
	}

	function ReadWsseSecurity(){
		$this->UserName = null;
		$this->Password = null;
		$data = file_get_contents('php://input');
		if(empty($data))
			return;
		$dom = new domDocument();
		$dom->loadXML($data, LIBXML_NOERROR | LIBXML_NOWARNING);
		$xpath = new DOMXPath($dom);
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$xpath->registerNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
		$xpath->registerNamespace('wsse', 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd');
		$nodes = $xpath->query("//SOAP-ENV:Envelope/SOAP-ENV:Header/wsse:Security/wsse:UsernameToken/wsse:Username");
		if($nodes->length > 0) {
            $this->UserName = CleanXMLValue($nodes->item(0)->nodeValue);
            getSymfonyContainer()->get("logger")->info("read SOAP UserName from request: {$this->UserName}");
        }
		$nodes = $xpath->query("//SOAP-ENV:Envelope/SOAP-ENV:Header/wsse:Security/wsse:UsernameToken/wsse:Password");
		if($nodes->length > 0)
			$this->Password = CleanXMLValue($nodes->item(0)->nodeValue);
	}

}

