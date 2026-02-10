<?php

class DebugCommandExecutor extends HttpCommandExecutor
{

	/**
	 * @var DebugProxyClient
	 */
	private $client;

	private $accountId;

	public function __construct($url, $client, $accountId)
	{
		parent::__construct($url);
		$this->client = $client;
		$this->accountId = $accountId;
	}

	/**
	 * @param WebDriverCommand $command
	 * @param array $curl_opts An array of curl options.
	 *
	 * @return mixed
	 */
	public function execute(WebDriverCommand $command)
	{
		$serialized = base64_encode(serialize(new SeleniumDebugRequest($this->url, $command)));
		$request = new DebugProxyRequest($this->accountId, $serialized);
		$response = $this->client->SendHttpRequest($request);
		$result = unserialize(base64_decode($response->SerializedResponse));
		if($result instanceof \Exception)
			throw $result;
		else
			return $result;
	}

}