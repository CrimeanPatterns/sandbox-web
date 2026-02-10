<?
class AutologinManager{

	private $arg;
	public  $successPages;
	private $preloadPages;
	private $preloadImages;
	private $anonymousPostUrl;
    public  $allowHTTPS = true;
	public  $supportedProtocols = [];

	/**
	 * @param  $id int - may be null
	 * @param  $arg - array of options
	 */
	public function __construct($id, $arg){
		unset($arg['RenewURL']);
		if(!array_key_exists('Anonymously', $arg))
			$arg['Anonymously'] = false;
		if(isset($_GET['To']) && isset($arg[$_GET['To'].'URL']))
			$arg['RenewURL'] = $arg[$_GET['To'].'URL'];
		if(isset($arg["RedirectURL"]) && isset($arg["RenewURL"])){
			$arg['RedirectURL'] = $arg['RenewURL'];
			unset($arg['RenewURL']);
		}
		unset($arg['PostValues']['submit']);

		// what to preload?
		$this->preloadPages = array();
		if(isset($arg['ClickURL']))
			$this->preloadPages[] = $arg['ClickURL'];
		if(isset($arg['CookieURL']))
			if(is_array($arg['CookieURL']))
				$this->preloadPages = array_merge($this->preloadPages, $arg['CookieURL']);
			else
				$this->preloadPages[] = $arg['CookieURL'];
		if((count($this->preloadPages) == 0) && !isset($arg['NoCookieURL']) && isset($arg['URL']))
			$this->preloadPages[] = $arg['URL'];
		
		// images
		$this->preloadImages = array();
		if(isset($arg['PreloadImages']))
			$this->preloadImages = array_merge($this->preloadImages, $arg['PreloadImages']);
		if(isset($arg['ImageURL']))
			$this->preloadImages[] = $arg['ImageURL'];
		if(isset($arg['PreloadAsImages']) && $arg['PreloadAsImages']){
			$this->preloadImages = array_merge($this->preloadImages, $this->preloadPages);
			$this->preloadPages = array();
		}


		// what to after load?
		$this->successPages = array();
		if(isset($arg['SuccessURL']))
			$this->successPages[] = $arg['SuccessURL'];
		if(isset($arg['RenewURL']))
			$this->successPages[] = $arg['RenewURL'];

		$this->arg = $arg;
		
		if($this->arg['Anonymously'] === true)
			$this->createAnonymousPostUrl();
	}

	public function drawPage(){
		?>
		<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="content-type" content="text/html; charset=UTF-8">
			<title>Auto login</title>
		</head>
		<body style="margin: 0px;" id="body" onload="startRedirect()">
		<?
		if(!isset($this->arg["RedirectURL"])){
			if(count($this->successPages) > 0)
				$target = "siteFrame";
			else
				$target = "_top";
			echo($this->drawForm($this->arg, $target));
		}
		?>
		<script type="text/javascript">

		var cookies_loaded = false;
		var logged_in = false;

		function TopRedirect(url){
			url = url.replace('&amp;', '&');
			if( window.frameElement != null )
				parent.location.href = url;
			else
				location.href = url;
		}

		var preloadPages = new Array();
		<?
		foreach($this->preloadPages as $n => $url)
			echo "preloadPages[$n] = ".json_encode($url).";\n";
		?>

		var successPages = new Array();
		<?
		foreach($this->successPages as $n => $url)
			echo "successPages[$n] = ".json_encode($url).";\n";
		?>

		var preloadImages = new Array();
		<?
		foreach($this->preloadImages as $n => $url)
			echo "preloadImages[$n] = ".json_encode($url).";\n";
		?>

		var pageIndex = -1;
		var loginStage = -2;

		function siteFrameLoaded(){
			switch(loginStage){
				case -1:
					// preload
					pageIndex++;
					if(pageIndex >= preloadPages.length){
						loginStage = 0;
						<?
						echo "
						if(typeof(parent.canLoginWithExtension) != 'undefined' && parent.canLoginWithExtension())
							parent.loginWithExtension();
						else\n";
							if( isset( $this->arg["RedirectURL"] ) )
								echo "TopRedirect('{$this->arg["RedirectURL"]}');\n";
							else
								echo "form_submit('form1');\n";
						?>
					}
					else{
						document.getElementById('siteFrame').src = preloadPages[pageIndex];
					}
					break;
				case 0:
					// login complete
					pageIndex = -1;
					loginStage = 1;
					siteFrameLoaded();
					break;
				case 1:
					// success pages
					pageIndex++;
					if(pageIndex == (successPages.length - 1)){
						// load last page to top
						TopRedirect(successPages[pageIndex]);
					}
					else{
						document.getElementById('siteFrame').src = successPages[pageIndex];
					}
					break;
			}
		}

		function form_submit(str) {
		<?if($this->arg['Anonymously'] === true):?>
		parent.url1 = "<?=$this->anonymousPostUrl;?>";
		parent.document.getElementById('checkAd').innerHTML = "<a href=\"#\" " +
			"onclick=\"window.open(url1);document.getElementById('checkAd').innerHTML = 'wait...';\">" +
			"Please click to visit <?=$this->arg["URL"] ?></a>";
		<?else:?>
			var form;
			if(document.layers){
				form = document.layers(str)
			}
			if(document.all){
				form = document.all(str);
			}
			if(!document.all && document.getElementById){
				form = document.getElementById(str);
			}
		<?
		if( isset( $this->arg["PostValues"]["submit"] ) )
			echo "		form['submit'].click();\n";
		else
			echo "		form.submit();\n";
		?>
		<?endif;?>
		}

		var preloadTimers = true;
		var preloadingImages = false;
		var preloadImageIndex = 0;

		function preloadAsImages(){
			preloadingImages = true;
			// this is as backup for old browsers
			for(n = 0; n < preloadImages.length; n++){
				setTimeout(function(){ if(preloadTimers) document.getElementById('preloadImage').src = preloadImages[n]; }, n * 4000);
			}
			setTimeout("if(preloadTimers) siteFrameLoaded()", n * 4000);
		}

		function startRedirect(){
			pageIndex = 0;
			loginStage = -1;
			// decide what scheme to use
            var firstUrl = null,
                supportedProtocols = <?=json_encode($this->supportedProtocols)?>;

            if(preloadPages.length > 0)
                firstUrl = preloadPages[0];
            if(preloadImages.length > 0)
                firstUrl = preloadImages[0];
            if(firstUrl){
                var matches = /^(http|https):/.exec(firstUrl.toLowerCase());
                if(matches){
                    if(matches[0] != window.parent.location.protocol && supportedProtocols.indexOf(matches[1]) !== -1 ){
                        window.parent.location.href = matches[0] + window.parent.location.href.substr(document.location.protocol.length);
                        return;
                    }
                }
            }

		<?
		if(isset($this->arg["RedirectURL"]) && count($this->preloadPages) == 0 && count($this->preloadImages) == 0){
			echo "
			if(typeof(parent.canLoginWithExtension) != 'undefined' && parent.canLoginWithExtension())
				parent.loginWithExtension();
			else
				TopRedirect(".json_encode($this->arg["RedirectURL"]).");\n";
		}
		else{
			if(count($this->preloadImages) > 0 || count($this->preloadPages) == 0)
				echo "preloadAsImages();\n";
			else
				echo "document.getElementById('siteFrame').src = ".json_encode($this->preloadPages[0])."\n";
		}
		?>
		}

		function imagePreloaded(){
			if(!preloadingImages)
				return;
			preloadTimers = false;
			preloadImageIndex++;
			if(preloadImageIndex < preloadImages.length)
				document.getElementById('preloadImage').src = preloadImages[preloadImageIndex];
			else
				<?
				if(count($this->preloadPages) > 0)
					echo "document.getElementById('siteFrame').src = ".json_encode($this->preloadPages[0])."\n";
				else
					echo "siteFrameLoaded();\n";
				?>
		}

		</script>

		<IFRAME name="siteFrame" onload="siteFrameLoaded()" src="about:blank" id="siteFrame"></IFRAME>
		<img id="preloadImage" onerror="imagePreloaded()" onload="imagePreloaded()" border="1"
			 src="/lib/images/pixel.gif" width="50" height="50">
		</body>
		</html>
	<?
	}

	private function drawForm($arg, $target) {
		$result = "<form style='margin:0px; padding: 0px;' name=\"form1\" id=\"form1\" method=\""
		.$arg["RequestMethod"] . "\" action=\"" . htmlspecialchars($arg["URL"]) . "\" target='{$target}'>\n";
		if (!isset($arg["PostValues"]))
			DieTrace("missing post values");
		foreach ($arg["PostValues"] as $k => $v) {
			if ($k == "submit")
				$result .= "<input type=\"submit\" name=\"" . htmlspecialchars($k) . "\" value=\"" . htmlspecialchars($v) . "\">\n";
			else
				$result .= "<input type=\"hidden\" name=\"" . htmlspecialchars($k) . "\" value=\"" . htmlspecialchars($v) . "\">\n";
		}
		$result .= "</form>\n";
		return $result;
	}
	
	private function createAnonymousPostUrl()
	{
		$chrNum = 100;
		$this->anonymousPostUrl = "javascript:a=document;"
			. "b='input';c=a.createElement('form');"
			. "c.method='{$this->arg["RequestMethod"]}';c.action='{$this->arg["URL"]}';c.target='siteFrame';";
		if (!isset($this->arg["PostValues"]))
			DieTrace("missing post values");
		foreach ($this->arg["PostValues"] as $k => $v) {
			$chr = chr($chrNum);
			$this->anonymousPostUrl .= "$chr=a.createElement(b);"
				. "$chr.type='hidden';$chr.name='$k';$chr.value=".json_encode($v).";"
				. "c.appendChild($chr);";
			$chrNum++;
		}
		$this->anonymousPostUrl .= "a.body.appendChild(c);c.submit();self.close();";
	}

}