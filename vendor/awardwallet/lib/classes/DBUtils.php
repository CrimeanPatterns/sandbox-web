<?

class DBUtils{

	/**
	 * create param, returns true if it does not exists, or expired
	 * @returns boolean
	 */
	public static function createExpirableParam($name, $ttl){
		// tried to implement silence period through APC, but apc cache dies with script termination
		// it does not work in cli mode, so doing this through database
		// it may go wrong in high multi-threading, should be rewritten to memcached in that case
		global $Connection;
		$q = new TQuery("select Val, Now() as TimeNow from Param where Name = '".addslashes($name)."'");
		$result = false;
		if($q->EOF
		||($Connection->SQLToDateTime($q->Fields['TimeNow']) - $Connection->SQLToDateTime($q->Fields['Val'])) > $ttl){
			$Connection->Execute("insert into Param(Name, Val) values('".addslashes($name)."', now())
			on duplicate key update Val = now()");
			$result = true;
		}
		return $result;
	}

}