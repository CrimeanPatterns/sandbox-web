<?php
class WsseAuthHeader extends SoapHeader {

	private $wss_ns = 'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd';

	function __construct($user, $pass, $ns = null) {
		if ($ns) {
			$this->wss_ns = $ns;
		}

		$auth = new stdClass();
		$auth->Username = new SoapVar($user, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);
		$auth->Password = new SoapVar($pass, XSD_STRING, NULL, $this->wss_ns, NULL, $this->wss_ns);

		$username_token = new stdClass();
		$username_token->UsernameToken = new SoapVar($auth, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns);

		$security_sv = new SoapVar(
			new SoapVar($username_token, SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'UsernameToken', $this->wss_ns),
			SOAP_ENC_OBJECT, NULL, $this->wss_ns, 'Security', $this->wss_ns);
		parent::__construct($this->wss_ns, 'Security', $security_sv, false);
	}
}

class TExtSoapClient extends SoapClient
{
	public $UserName;
	public $Password;
    protected $options;

	public function __construct($wsdl, $options = null){
		parent::__construct($wsdl, $options);
        $this->options = $options;
		if(!isset($options['wsse-login']) || !isset($options['wsse-password']))
			DieTrace("wsse-login and wsse-password required in options");
		$this->UserName = $options['wsse-login'];
		$this->Password = $options['wsse-password'];
		$wsse_header = new WsseAuthHeader($this->UserName, $this->Password);
		$this->__setSoapHeaders($wsse_header);
	}

//    public function __doRequest($request, $location, $action, $version)
//    {
//        /*
//         * $request is a XML string representation of the SOAP request
//         * that can e.g. be loaded into a DomDocument to make it modifiable.
//         */
//        $domRequest = new DOMDocument();
//        $domRequest->loadXML($request);
//
//        // modify XML using the DOM API, e.g. get the <s:Header>-tag
//        // and add your custom headers
//        $xp = new DOMXPath($domRequest);
//        $xp->registerNamespace('SOAP-ENV', 'http://schemas.xmlsoap.org/soap/envelope/');
//
//        // now add your custom header
//		if(isset($this->UserName)){
//			// fails if no <s:Header> is found - error checking needed
//			$envelope = $xp->query('/SOAP-ENV:Envelope')->item(0);
//			if(!isset($envelope))
//				DieTrace("Envelope not found");
//			$header = $xp->query('/SOAP-ENV:Envelope/SOAP-ENV:Header')->item(0);
//			if(!isset($header)){
//				// create header
//				$header = $domRequest->createElementNS('http://schemas.xmlsoap.org/soap/envelope/', 'SOAP-ENV:Header');
//				$body = $xp->query('/SOAP-ENV:Envelope/SOAP-ENV:Body')->item(0);
//				$envelope->insertBefore($header, $body);
//			}
//			$security = $domRequest->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:Security');
//			$usernameToken = $domRequest->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:UsernameToken');
//			$security->appendChild($usernameToken);
//			$username = $domRequest->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:Username', $this->UserName);
//			$password = $domRequest->createElementNS('http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd', 'wsse:Password', $this->Password);
//			$usernameToken->appendChild($username);
//			$usernameToken->appendChild($password);
//			$header->appendChild($security);
//		}
//        $request = $domRequest->saveXML();
//        return parent::__doRequest($request, $location, $action, $version);
//    }
}
?>