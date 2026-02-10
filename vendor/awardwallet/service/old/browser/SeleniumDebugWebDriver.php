<?php

class SeleniumDebugWebDriver extends RemoteWebDriver
{

    /**
     * @var \AwardWallet\Common\Selenium\ServerInfo
     */
    private $serverInfo;

    public function setServerInfo(\AwardWallet\Common\Selenium\ServerInfo $serverInfo)
    {
        $this->serverInfo = $serverInfo;
    }

    public static function createWithoutSession(
		$url = 'http://localhost:4444/wd/hub',
		$timeout_in_ms = 300000,
        \AwardWallet\Common\Selenium\ServerInfo $serverInfo
	)
	{
		$url = preg_replace('#/+$#', '', $url);

		$executor = new HttpCommandExecutor($url);
		$executor->setConnectionTimeout($timeout_in_ms);
        if(method_exists($executor, 'setRequestTimeout')) // TODO: remove after upgrading all projects
		    $executor->setRequestTimeout($timeout_in_ms);

		$driver = new static();
        $driver->setServerInfo($serverInfo);
		$driver->setCommandExecutor($executor);

		return $driver;
	}

	public function createNewSession($desired_capabilities = null){
		// Passing DesiredCapabilities as $desired_capabilities is encourged but
		// array is also accepted for legacy reason.
		if ($desired_capabilities instanceof DesiredCapabilities) {
			$desired_capabilities = $desired_capabilities->toArray();
		}

		$command = new WebDriverCommand(
			null,
			DriverCommand::NEW_SESSION,
			array('desiredCapabilities' => $desired_capabilities)
		);
		$response = $this->executor->execute($command);
        $value = $response->getValue();
        if (isset($value['error'])) {
            throw new \Exception($value['error'] . ": " . $value["message"]);
        }

		$this->setSessionID($response->getSessionID());
	}

    public function getServerInfo() : \AwardWallet\Common\Selenium\ServerInfo
    {
        return $this->serverInfo;
    }

}