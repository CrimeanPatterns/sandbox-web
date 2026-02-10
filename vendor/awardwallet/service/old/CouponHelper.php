<?php

require_once(__DIR__."/functions.php" );

class CouponHelper {
	
	protected $checker;
	protected $accountID;
	
	public function __construct($accountID) {
		global $sPath;
		
		$q = new TQuery( "SELECT a.*, p.Code as ProviderCode, p.Engine AS ProviderEngine
						  FROM Account a INNER JOIN Provider p 
						  ON a.ProviderID = p.ProviderID
						  WHERE a.AccountID = $accountID");
		if( $q->EOF )
			return false;
		$q->Fields["Pass"] = DecryptPassword( $q->Fields["Pass"] );
		if ( isset($q->Fields["SavePassword"]) && !LoadCookiePassword( $q->Fields ) )
			return false;
		
		require_once $sPath."/service/TAccountChecker.php";
		require_once $sPath."/engine/".$q->Fields["ProviderCode"]."/functions.php";
		
		$this->checker = GetAccountChecker($q->Fields["ProviderCode"]);
		$this->checker->SetAccount($q->Fields);
		$this->accountID = $accountID;
	}
	
	public function Login() {
		$this->checker->InitBrowser();
		if ($this->checker->LoadLoginForm() && $this->checker->Login())
			return true;
		
		return false;
	}
	
	/**
	 * Mark coupon (as used or unused)
	 * 
	 * @param array $ids array["id"] = "used" (true or false)
	 * @return array|bool The result for each coupon
	 */
	public function MarkCoupon(array $ids) {
		if ($this->Login()) {
			return $this->checker->MarkCoupon($ids);
		}
		
		return false;
	}

	public function downloadCertificate($link) {
		if ($this->Login()) {
			if(!$this->checker->http->GetURL($link) || !isset($this->checker->http->Response['headers']['content-type']))
				Redirect("couponViewFailed.php?AccountID=".$this->accountID);
			ob_clean();
			header("Content-Type: ".$this->checker->http->Response['headers']['content-type']);
			header("Content-Disposition: inline; filename=certificate.pdf");
			echo $this->checker->http->Response['body'];
		}
	}

	public static function storeDeal($key, $var, $ttl) {
		return;
		//apc_store($key, $var, $ttl);
	}
	
	public static function fetchDeal($key) {
		return false;
		//return apc_fetch($key);
	}
	
}

?>