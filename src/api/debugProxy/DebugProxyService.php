<?php

class DebugProxyService extends SoapService {

	public function Authenticate(){
  		$this->ReadWsseSecurity();

		if(
			isset($_SESSION['DebugProxyAuth'])
			&& $_SESSION['DebugProxyAuth']['UserName'] == $this->UserName
			&& $_SESSION['DebugProxyAuth']['Password'] == $this->Password
			&& $_SESSION['DebugProxyAuth']['Expiration'] > time()
		)
			return true;

        $locked = getSymfonyContainer()->get("aw.security.antibruteforce.password")->checkForLockout("debug_proxy_v2_" . $_SERVER['REMOTE_ADDR']);
        if(!empty($locked)) {
            getSymfonyContainer()->get("logger")->warning("debug proxy lockout for {$_SERVER['REMOTE_ADDR']}");
            return false;
        }

		require_once __DIR__ . '/../oauth2/AWOAuth2.php';
        $oauth = new AWOAuth2();
        $_GET['oauth_token'] = $this->Password;
        if (!$oauth->verifyAccessToken('debugProxy', false, false, false, false)) {
            getSymfonyContainer()->get("logger")->warning("failed debug proxy auth for {$_SERVER['REMOTE_ADDR']}");
            return false;
        }

        $_SESSION['DebugProxyAuth'] = [
            'UserName' => $this->UserName,
            'Password' => $this->Password,
            'Expiration' => time() + 180
        ];

        $this->UserName = getSymfonyContainer()->get("database_connection")->executeQuery("
        select
            u.Login
        from
            Usr u 
            join OA2Token t on u.UserID = t.UserID
        where 
            t.Token = ?", [$this->Password])->fetch(\PDO::FETCH_COLUMN);

		return true;
 	}
	
	public function GetAccountInfo(AccountInfo $request) {
		require_once "AccountInfoResponse.php";

		$pvRep = getSymfonyContainer()->get('doctrine')->getRepository('AwardWalletMainBundle:Passwordvault');
        $hasAccessToPassword = $pvRep->hasAccessScalar($request->AccountID, $this->UserName);

		$info = $this->retrieveAccountInfo($request->AccountID, $hasAccessToPassword);
		if ($info == false)
			return new SoapFault("Server", "Account not found or the password is saved locally");

		$response = new AccountInfoResponse(
			$info['Login'], $info['Login2'], $info['Login3'], $info['Pass'],
			$info['ProviderID'], $info['DisplayName'], $info['Code'], $info['Engine'], base64_encode($info['BrowserState']),
			base64_encode(serialize(SQLToArray("select Question, concat('AW_SEC_ANSWER_', AnswerID) as Answer from Answer where AccountID = ".$request->AccountID, "Question", "Answer")))
		);

		return $response;
	}
	
	private function encodePassword($content, $in, $out, $excludingUrl = false) {
		if (is_array($content)) {
			foreach ($content as $k=>$v) {
				$content[$k] = $this->encodePassword($v, $in, $out, $excludingUrl);
			}
		} elseif (is_object($content)) {
			foreach ($content as $k=>$v) {
				$content->$k = $this->encodePassword($v, $in, $out, $excludingUrl);
			}
		} elseif (is_scalar($content) && !is_bool($content) && !is_int($content) && !is_float($content)) {
			if (!$excludingUrl || !$this->isUrl(trim($content)))
				return str_replace(strtolower($in), strtolower($out), str_replace($in, $out, $content));
		}
		
		return $content;	
	}

	private function isUrl($url) {
		if (preg_match("/^((ftp|https?):\/\/)?([a-z\d\_\-]+\.)/ims", $url)
			|| preg_match("/^(\/[\w\_\-]+)+/ims", $url)
			|| preg_match("/^(\.[\w\_\-]+)+/ims", $url))
			return true;

		return false;
	}

	private function getAnswers($accountId){
		return SQLToArray("select concat('AW_SEC_ANSWER_', AnswerID) as Placeholder, Answer from Answer where AccountID = ".$accountId, "Placeholder", "Answer");
	}

	private function decodeAnswers($content, $accountId){
		$answers = $this->getAnswers($accountId);
		foreach($answers as $placeholder => $answer)
			$content = $this->encodePassword($content, $placeholder, $answer);
		return $content;
	}
	
	private function encodeAnswers($content, $accountId){
		$answers = $this->getAnswers($accountId);
		foreach($answers as $placeholder => $answer)
			$content = $this->encodePassword($content, $answer, $placeholder);
		return $content;
	}

	private function retrieveAccountInfo($accountID, $getPassword = false) {
		$pass = ($getPassword) ? ' a.Pass,' : "'" . DebugProxyClient::UNKNOWN_PASSWORD . "' as Pass,";
		$row = SQLToArray("SELECT a.ProviderID, a.Login,".$pass." a.Login2,
			a.Login3, a.BrowserState, p.Code, p.DisplayName, p.Engine
			FROM Account a JOIN Provider p ON a.ProviderID = p.ProviderID
			WHERE a.AccountID = ".$accountID." /*AND a.State = ".ACCOUNT_ENABLED."*/ AND a.SavePassword = ".SAVE_PASSWORD_DATABASE."",
			"ProviderID", "Login", true);
		if (!sizeof($row))
			return false;
		if ($getPassword)
			$row[0]['Pass'] = DecryptPassword($row[0]['Pass']);
			
		return $row[0];
	}

	public function SendHttpRequest(DebugProxyRequest $request)
	{
		$accountId = intval($request->AccountID);
		$acc = new TQuery("select * from Account where AccountID = ".$accountId);
		if($acc->EOF)
			throw new SoapFault("accountNotFound", "Account {$accountId} not found");
		$pass = DecryptPassword($acc->Fields['Pass']);

		$driverRequest = unserialize(base64_decode($request->SerializedRequest));

		if($driverRequest instanceof SeleniumDebugRequest){
			try {
				$executor = new HttpCommandExecutor($driverRequest->url);
				$command = new WebDriverCommand($driverRequest->command->getSessionID(), $driverRequest->command->getName(), $this->decodeAnswers($this->encodePassword($driverRequest->command->getParameters(), DebugProxyClient::UNKNOWN_PASSWORD, $pass), $accountId));
				$response = $executor->execute($command);
			}
			catch(\Exception $e){
				$response = $e;
			}
		}
		else {
			$driver = new CurlDriver();
			$driver->start();
			$driverRequest = $this->decodeAnswers($this->encodePassword($driverRequest, DebugProxyClient::UNKNOWN_PASSWORD, $pass), $accountId);
			$response = $driver->request($driverRequest);
		}

		return new DebugProxyResponse(base64_encode(serialize($this->encodeAnswers($this->encodePassword($response, $pass, DebugProxyClient::UNKNOWN_PASSWORD, true), $accountId))));
	}
}

