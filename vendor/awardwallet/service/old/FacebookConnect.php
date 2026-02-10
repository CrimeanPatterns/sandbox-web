<?php

/**
 * FacebookConnect
 * 
 * Class for login through service Facebook Connect
 */
class FacebookConnect {
	
	const COMMAND_LINE = '[FacebookConnect]: ';
	
	/**
	 * Error codes
	 */
	const CODE_UNKNOWN 						= 1;
	const CODE_NOT_FOUND_LOGIN_FORM 		= 2;
	const CODE_HTTP_ERROR					= 3;
	const CODE_USER_INTERVENTION_REQUIRED	= 4;
	const CODE_NOT_FOUND_SESSION			= 5;
	const CODE_INVALID_PASSWORD				= 6;
	const CODE_LOCK_ACCOUNT					= 7;
	
	public $lastError = null;

	/**
	 * @var object
	 */
	public $userInfo;
	
	/**
	 * @var string
	 */
	protected $appId;
	
	/**
	 * @var string
	 */
	protected $signedRequest = null;
	
	/**
	 * @var string
	 */
	protected $state;
	
	/**
	 * @var string
	 */
	protected $redirectUri;
	
	/**
	 * @var callback
	 */
	protected $callbackFunction;
	
	/**
	 * @var TAccountChecker
	 */
	protected $checker;
	
	/**
	 * @var array
	 */
	protected $credentials = array(
		'Login' 	=> '',
		'Password'	=> ''
	);
	
	/**
	 * @var array
	 */							   
	protected $_domain = array(
		'api' => 'https://api.facebook.com/',
		'api_read' => 'https://api-read.facebook.com/',
		'cdn' => 'http://static.ak.fbcdn.net/',
		'https_cdn' => 'https://s-static.ak.fbcdn.net/',
		'graph' => 'https://graph.facebook.com/',
		'staticfb' => 'http://static.ak.facebook.com/',
		'https_staticfb' => 'https://s-static.ak.facebook.com/',
		'https_www' => 'https://www.facebook.com/',
		'www' => 'http://www.facebook.com/',
	);

	/**
	 * @var string
	 */
	protected $baseDomainCookie;
								
	/**
	 * @var null|bool
	 */
	protected $allowAccess = null;
	
	/**
	 * @var array 
	 */
	protected $permissions = array();
	
	public function __construct($appId = null) {
		if (!is_null($appId))
			$this->setAppId($appId);
	}
	
	/**
	 * Set the Application ID
	 *
	 * @param string $appId the Application ID
	 * @return FacebookConnect
	 */
	public function setAppId($appId) {
		$this->appId = $appId;
		return $this;
	}
	
	/**
	 * Get the Application ID
	 *
	 * @return string the Application ID
	 */
	public function getAppId() {
		return $this->appId;
	}
	
	/**
	 * Set the Signed Request
	 *
	 * @param string $signedRequest
	 * @return FacebookConnect
	 */
	public function setSignedRequest($signedRequest) {
		$this->signedRequest = $signedRequest;
		return $this;
	}
	
	/**
	 * Get the Signed Request
	 *
	 * @return string the Signed Request
	 */
	public function getSignedRequest() {
		return $this->signedRequest;
	}
	
	/**
	 * Set the Redirect URI
	 *
	 * @param string $uri the Redirect URI
	 * @return FacebookConnect
	 */
	public function setRedirectURI($uri) {
		$this->redirectUri = $uri;
		
		return $this;
	}
	
	/**
	 * Get the Redirect URI
	 *
	 * @return string the Redirect URI
	 */
	public function getRedirectURI() {
		return $this->redirectUri;
	}
	
	/**
	 * Set the Callback Function
	 *
	 * @param mixed $function the Callback
	 * @return FacebookConnect
	 */
	public function setCallbackFunction($function) {
		if (is_callable($function))
			$this->callbackFunction = $function;
		return $this;
	}
	
	/**
	 * Get the Callback Function
	 *
	 * @return mixed the Callback
	 */
	public function getCallbackFunction() {
		return $this->callbackFunction;
	}

	/**
	 * @param string $domain
	 * @return FacebookConnect
	 */
	public function setBaseDomain($domain) {
		$this->baseDomainCookie = $domain;
		return $this;
	}

	/**
	 * @param string $token
	 * @return mixed|null|object
	 */
	public function getUserInfo($token) {
		$this->Log('Get user info...');
		$url = $this->getUrl(
			$this->_domain['graph'],
			'me',
			array(
				 'access_token' => $token,
			)
		);
		$this->Log('User info URL: '.$url);
		$this->checker->http->GetURL($url);
		$response = json_decode($this->checker->http->Response['body']);
		$this->Log('User info response: '.$this->checker->http->Response['body']);
		if (isset($response->id))
			return $this->userInfo = $response;

		return $this->userInfo = null;
	}
	
	/**
	 * Set the TAccountChecker
	 *
	 * @param TAccountChecker $checker
	 * @return FacebookConnect
	 */
	public function setChecker(TAccountChecker $checker) {
		$this->checker = $checker;
		return $this;
	}
	
	/**
	 * Set the login and password
	 *
	 * @param string $login
	 * @param string $password
	 * @return FacebookConnect
	 */
	public function setCredentials($login, $password) {
		$this->credentials['Login'] 	= $login;
		$this->credentials['Password'] 	= $password;
		return $this;
	}
	
	/**
	 * Set the CSRF state token
	 *
	 * @param string $state token
	 * @return FacebookConnect
	 */
	public function setCSRFTokenState($state) {
		$this->state = $state;
		return $this;
	}
	
	/**
	 * Allow access to the information Facebook
	 *
	 * @return FacebookConnect
	 */
	public function AllowAccess() {
		$this->allowAccess = true;
		return $this;
	}
	
	/**
	 * Deny access to the information Facebook
	 *
	 * @return FacebookConnect
	 */
	public function DenyAccess() {
		$this->allowAccess = false;
		return $this;
	}
	
	/**
	 * Get an array of permissions
	 *
	 * @return array
	 */
	public function getPermissions() {
		return $this->permissions;
	}
	
	/**
	 * Trying to get a link to autologin
	 *
	 * @return null|string
	 */
	public function getAutoLoginLink() {
		return $this->getRedirectURI();
	}
	
	/**
	 * Preparing login form. Needed for auto-login
	 * 
	 * @param array $loginUrlParams provide custom parameters
	 * @return bool
	 */
	public function PrepareLoginForm($loginUrlParams = array()) {
		$this->checker->http->setDefaultHeader('Referer', $this->getRedirectURI());
		$this->Log('Get login url...');
		$loginURL = $this->getLoginUrl($loginUrlParams);
		$this->Log('Login URL = ' . $loginURL);
		$this->checker->http->GetURL($loginURL);
		$this->Log('Search the login form on the website Facebook.com...');
		if (!$this->checker->http->ParseForm("login_form"))
			throw new FacebookException($this->lastError = 'Not found login form', self::CODE_NOT_FOUND_LOGIN_FORM);
			
		$this->Log('Login form was found. Login...');
		$this->checker->http->Form['email'] = $this->credentials['Login'];
		$this->checker->http->Form['pass'] = $this->credentials['Password'];
		
		return true;
	}
	
	/**
	 * Implementation login through Facebook Connect
	 *
	 * @param array $loginStatusUrlParams provide custom parameters
	 * @return FacebookConnect
	 */
	public function Login($loginStatusUrlParams = array()) {
		if (!$this->checker->http->PostForm())
			throw new FacebookException($this->lastError = 'When submitting a form problems', self::CODE_HTTP_ERROR);

		# Permissions request
		if ($this->checker->http->ParseForm("platformDialogForm")
		&& preg_match("/facebook\.com/ims", $this->checker->http->currentUrl())) {
			/*$temp = $this->checker->http->FindNodes("//*[@class='fsl']//text()");
			if (sizeof($temp)) {
				$temp = implode(' ', $temp);
				if (preg_match("/:([^:]+)/ims", $temp, $matches))
					$this->permissions = array($matches[1]);
			}
			if (!sizeof($this->permissions))
				$this->sendMail('Incorrectly received permission');
			*/
			if (is_null($this->allowAccess))
				throw new FacebookException($this->lastError = 'To allow access to information', self::CODE_USER_INTERVENTION_REQUIRED);
				
			if ($this->allowAccess) {
				$this->Log('Access Granted...');
				$this->checker->http->Form['__CONFIRM__'] = 1;
			} else {
				$this->Log('Access Denied...');
				$this->checker->http->Form['__CANCEL__'] = 1;
			}
			
			if (!$this->checker->http->PostForm())
				throw new FacebookException($this->lastError = 'When submitting a form problems', self::CODE_HTTP_ERROR);
		}

		$this->checkErrors();

		$this->Log('Checking the created session...');
		$session = $this->getLoginStatus();
		if (is_null($session)) {
			$this->Log('Session not found');
			$this->sendMail('Session not found');
			throw new FacebookException($this->lastError = 'Session not found', self::CODE_NOT_FOUND_SESSION);
		}
		$this->Log('Session data: '. var_export($session, true));
		if (isset($session['signed_request'])) {
			$this->Log('Signed request found! ['.$session['signed_request'].']');
			$this->setSignedRequest($session['signed_request']);
		} else {
			$this->Log('signed_request not found');
			$this->sendMail('signed_request not found');
			throw new FacebookException($this->lastError = 'Session not found', self::CODE_NOT_FOUND_SESSION);
		}
		if (isset($session['access_token']))
			$this->getUserInfo($session['access_token']);

		$this->setCookies($this->baseDomainCookie);

		# Callback
		$this->execCallbackRequest($session, $this, $this->checker);
		
		return $this;
	}

	protected function setCookies($baseDomain) {
		$this->checker->http->getCookieManager()->addCookie(array(
			"name" => "fbsr_".$this->getAppId(),
			"path" => "/",
			"domain" => $baseDomain,
			"expires" => "Wed, 18-11-2019 10:57:52 GMT",
			"value" => $this->getSignedRequest(),
		));
		$this->checker->http->getCookieManager()->addCookie(array(
			"name" => "fbm_".$this->getAppId(),
			"path" => "/",
			"domain" => $baseDomain,
			"expires" => "Wed, 18-11-2019 10:57:52 GMT",
			"value" => 'base_domain='.$baseDomain,
		));
	}

	protected function checkErrors($attempt = 1) {
		if ($attempt >= 6) {
			$this->sendMail('Unknown error');
			throw new FacebookException($this->lastError = 'Unknown error', self::CODE_NOT_FOUND_SESSION);
		}

		# Invalid username or password
		if ( ($message = $this->checker->http->FindSingleNode("//div[contains(@class, 'login_error_box')]/div[@class]"))
			&& preg_match("/www\.facebook\.com\/login\.php/ims", $this->checker->http->currentUrl())) {
			throw new FacebookException($this->lastError = $message, self::CODE_INVALID_PASSWORD);
		}
		# Your account is temporarily locked
		# TODO: i18n message
		if ( $this->checker->http->FindPreg("/Your account is temporarily locked/ims") ) {
			throw new FacebookException($this->lastError = 'Your account is temporarily locked', self::CODE_LOCK_ACCOUNT);
		}
		# Remember Browser
		if (preg_match("/facebook\.com\/checkpoint/ims", $this->checker->http->currentUrl())
			&& $this->checker->http->ParseForm(null, 1, true, "//form[@class='checkpoint']")
			&& $this->checker->http->FindNodes("//input[@name='name_action_selected']")
		) {
			$this->Log('Remember Browser page');
			$this->checker->http->Form['name_action_selected'] = 1;
			$submitButton = "//form[@class='checkpoint']//input[@type='submit']";
			$this->checker->http->Form[$this->checker->http->FindSingleNode($submitButton.'/@name')] = $submitButton.'/@value';
			if (!$this->checker->http->PostForm())
				throw new FacebookException($this->lastError = 'When submitting a form problems', self::CODE_HTTP_ERROR);
			return $this->checkErrors($attempt+1);
		}
		# Other page
		if (preg_match("/facebook\.com\/checkpoint/ims", $this->checker->http->currentUrl())
			&& $this->checker->http->ParseForm(null, 1, true, "//form[@class='checkpoint']")
			&& sizeof($this->checker->http->FindSingleNode("//label[last()]/input[@type='submit']"))
		) {
			$this->Log('Other page');
			$submitButton = "//label[last()]/input[@type='submit']";
			$this->checker->http->Form[$this->checker->http->FindSingleNode($submitButton.'/@name')] = $submitButton.'/@value';
			if (!$this->checker->http->PostForm())
				throw new FacebookException($this->lastError = 'When submitting a form problems', self::CODE_HTTP_ERROR);
			return $this->checkErrors($attempt+1);
		}
	}

	/**
	 * @return null if unsuccessful login
	 * @return array session data
	 */
	public function getLoginStatus() {
		$parse = function($url) {
			$result = array();
			$parseData = parse_url($url);
			if (!isset($parseData['fragment']))
				return array();

			$a = explode('&', $parseData['fragment']);
			foreach ($a as $query) {
				$b = explode('=', $query);
				$result[urldecode($b[0])] = urldecode($b[1]);
			}
			return $result;
		};
		$url = $this->checker->http->currentUrl();
		$result = $parse($url);
		if (!sizeof($result) || preg_match("/facebook\.com/ims", $url)) {
			# get redirect uri
			$result = array();
			if (preg_match("/window\.location\.href=[\'\"]([^\'\"]+)/ims", $this->checker->http->Response['body'], $matches)) {
				$url = str_replace("\\", "", $matches[1]);
				$this->Log('Link was found: '.$url);
				$this->checker->http->GetURL($url);
				$result = $parse($url);
				if (!sizeof($result) || preg_match("/facebook\.com/ims", $url)) {
					$result = array();
				}
			}
		}

		$this->Log('Login status: '.var_export($result, true));
		if (!sizeof($result))
			return null;
		return $result;
	}
	
	/**
	 * Verifying login
	 * 
	 * @param string $pattern Xpath or RegExp
	 * @param bool 	 $xpath use Xpath? If not, then RegExp
	 * @return bool
	 */
	public function isLogIn($pattern, $xpath = true) {
		if ($xpath) {
			if ($this->checker->http->FindSingleNode($pattern))
				return true;
		} else {
			if ($this->checker->http->FindPreg($pattern))
				return true;
		}
		
		return false;
	}
	
	/**
	 * The parameters:
	 * - redirect_uri: the url to go to after a successful login
	 * - scope: comma separated list of requested extended perms
	 *
	 * @param array $params provide custom parameters
	 * @return string the URL for the login flow
	 */
	public function getLoginUrl($params = array()) {
		$this->establishCSRFTokenState();
		return $this->getUrl(
			$this->_domain['https_www'],
			'dialog/oauth',
			array_merge(array(
				'client_id' => $this->getAppId(),
				'redirect_uri' => $this->getRedirectURI(),
				'response_type' => 'token,signed_request',
				'state' => $this->state),
				$params
			)
		);
	}
	
	/**
	 * Build the URL for given domain, path and parameters.
	 *
	 * @param string $name the name of the domain
	 * @param string $path optional path (without a leading slash)
	 * @param array $params Array optional query parameters
	 * @return string the URL for the given parameters
	 */
	protected function getUrl($name, $path = '', $params = array(), $href = null) {
	    if ($path) {
			if ($path[0] === '/') {
	        	$path = substr($path, 1);
	      	}
	      	$name .= $path;
	    }
	    if ($params) {
			$name .= '?' . http_build_query($params, null, '&');
	    }
	    if (isset($href))
	    	$name .= '#'.$href;
	
	    return $name;
	}
	
	/**
	 * Lays down a CSRF state token for this process
	 *
	 * @return void
	 */
	protected function establishCSRFTokenState() {
		if ($this->state === null)
			$this->state = md5(uniqid(mt_rand(), true));
	}
	
	/**
	 * Sending the message to the log
	 *
	 * @param string $str
	 * @return void
	 */
	protected function Log($str) {
		$this->checker->http->Log(FacebookConnect::COMMAND_LINE . $str);
	}

	protected function sendMail($subject, $body = null) {
		$body = (isset($body)) ? $body : $this->checker->http->Response['body'];
		mailTo(
			ConfigValue(CONFIG_ERROR_EMAIL),
			'FacebookConnect: '.$subject,
			var_export(array('body' => $body), true),
			str_ireplace("text/plain", "text/html", EMAIL_HEADERS)
		);
	}
	
	/**
	 * Execute callback request.
	 * The function is passed three arguments:
	 *   $session - JSON object. Contains: 
	 * 					$session->access_token - unique key
	 * 					$session->base_domain - provider domain
	 * 					$session->expires
	 * 					$session->secret - secret key
	 * 					$session->session_key
	 * 					$session->sig
	 * 					$session->uid - User ID
	 * 	 Instance FacebookConnect
	 * 	 Instance TAccountChecker
	 * 
	 * @param mixed $session JSON object
	 * @param FacebookConnect $fc
	 * @param TAccountChecker $checker
	 * @return bool
	 */
	protected function execCallbackRequest($session, $fc, $checker) {
		if (!is_callable($this->getCallbackFunction()))
			return false;
		
		call_user_func_array($this->getCallbackFunction(), array($session, $fc, $checker));
		
		return true;
	}
}

class FacebookException extends Exception {
	
}

?>