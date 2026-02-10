<?
class PageCache{

	protected static function pageId(){
		$params = $_GET;
		unset($params['NoCache']);
		$qs = var_export($params, true);
		return $_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."_".md5($qs).ArrayVal($_SERVER, 'HTTP_VIA_AW_PROXY')."_v2";
	}

	protected static function canCache(){
		return $_SERVER['REQUEST_METHOD'] == 'GET' && !isset($_GET['NoCache']);
	}

	static function showCache($cacheTime, $lockTime = 300, $lockName = null){
		if(self::canCache())
			$content = Cache::getInstance()->get(self::pageId());
		else
			$content = false;
		if($content === false){
			if(!isset($lockName))
				$lockName = self::pageId()."_lock";
			if(!Cache::getInstance()->add($lockName, true, time(), $lockTime))
				die("this page is updating by another user, please wait (up to ".($lockTime / 60)." minutes)");
			register_shutdown_function(array("PageCache", "recordCache"), $cacheTime, $lockName);
			return false;
		}
		if(preg_match("/<!-- cache:(\d+) -->/ims", $content, $matches)){
			$qs = $_SERVER['QUERY_STRING'];
			if($qs != "")
				$qs .= "&";
			$qs .= "NoCache=1";
			$content = str_replace($matches[0], "<div style='clear: both; padding-top: 10px; color: gray;'>this is cached page, page was generated ".round((time() - $matches[1]) / 60)." minutes ago (".date("Y-m-d H:i:s", $matches[1]).").
			you can <a href='?{$qs}'>request actual version</a></div>", $content);
		}
		echo $content;
		exit();
	}

	static function recordCache($cacheTime, $lockName){
		Cache::getInstance()->set(self::pageId(), ob_get_contents(), $cacheTime);
		Cache::getInstance()->delete($lockName);
	}

}