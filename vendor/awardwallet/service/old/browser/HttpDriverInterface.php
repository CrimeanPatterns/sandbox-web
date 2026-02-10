<?
interface HttpDriverInterface{

	public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null);
	public function stop();

	/**
	 * @return boolean
	 */
	public function isStarted();

	/**
	 * @return HttpDriverResponse
	 */
	public function request(HttpDriverRequest $request);

	/**
	 * @return array
	 */
	public function getState();

	public function setState(array $state);

	public function setLogger(HttpLoggerInterface $logger);

}