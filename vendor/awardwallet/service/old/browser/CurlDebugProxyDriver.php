<?
require_once __DIR__ . '/HttpDriverInterface.php';

class CurlDebugProxyDriver implements HttpDriverInterface
{

	/**
	 * @var DebugProxyClient
	 */
	protected $debugProxyClient;
	/**
	 * @var int
	 */
	protected $accountId;

	public function __construct(DebugProxyClient $debugProxyClient, $accountId){
		$this->debugProxyClient = $debugProxyClient;
		$this->accountId = $accountId;
	}

	public function start($proxy = null, $proxyLogin = null, $proxyPassword = null, $userAgent = null)
	{
	}

	public function isStarted()
	{
		return true;
	}

	public function stop()
	{
	}

	public function request(HttpDriverRequest $request)
	{
		return unserialize(base64_decode($this->debugProxyClient->SendHttpRequest(new DebugProxyRequest($this->accountId, base64_encode(serialize($request))))->SerializedResponse));
	}

	public function getState()
	{
		return [];
	}

	public function setState(array $state)
	{

	}

	public function setLogger(HttpLoggerInterface $logger){

	}
}