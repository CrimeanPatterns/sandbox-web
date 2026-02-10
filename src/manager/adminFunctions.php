<?

function formatLogFile($s, $format, $noScripts){
	// TBaseBrowser format
	$s = preg_replace_callback("/(GET|POST):\s*([^<]+)<br>(.+?)\s*saved (step\d\d\.html)/ims", "formatLogFileCallback", $s);
	// HttpBrowser format
	$s = preg_replace_callback("/saved ([^<]+)<!\-\- url:([^ ]*) \-\->/ims", "formatHttpLogFileCallback", $s);
	$s = preg_replace_callback("/(\b)(\d{10})(,)?([\s\n<]+)/", function ($match) {
        if (((string) (int) $match[2] === $match[2]) && strpos($match[2], '1') === 0) {
            return $match[1].$match[2].$match[3].' <span style="color: gray;">// '.date('H:i d M Y', $match[2]).'</span>'.$match[4];
        } else {
            return $match[1].$match[2].$match[3].$match[4];
        }
	}, $s);
    if ($noScripts) {
        header("Content-Security-Policy: script-src 'self'; cookie-scope 'none';");
		$s = preg_replace('/window\.location\.href\s*=\s*["\'][^"\']*NoCookie[^"\']*["\']/ims', 'nocookie = "nocookie cut by awardwallet"', $s);
		$s = preg_replace('/if\s*\([^;]*NoCookie[^;]*;/ims', 'nocookie = "nocookie cut by awardwallet";', $s);
		$s = preg_replace('/document.location.href\s*=\s*[\'"].*cancel.*[\'"]/ims', 'nocookie = "nocookie cut by awardwallet";', $s);
//		$script = ''.'<script type="text/javascript">
//			function AwardWalletPreventReload() { return "### AwardWallet Log File ###\nPage want to redirect you! Or you want to close it."; }
//			window.onbeforeunload = AwardWalletPreventReload;
//			window.onunload = AwardWalletPreventReload;
//		</script>';
//		$s = $script . $s;
	}
	if($format == 'source'){
		$s = mb_convert_encoding($s, "utf-8", "utf-8"); // some logs were converted to empty string in htmlspecialchars below withput this
		$s = "<pre><code>".htmlspecialchars($s)."</code></pre>";
	}
	if ($format == 'image') {
		$s = '<img src="data:image/png;base64,'.base64_encode($s).'">';
	}
	return $s;
}

function formatClassesLogFile($s) {
	$blocks = [
		'Account Check Parameters',
		'Load Login Form',
		'Login',
		'Parse',
		'Parse Itineraries',
		'Parse History',
		'Account Check Result',
	];
	foreach ($blocks as $block) {
		$search = sprintf('<h2 class="awlog-info">%s</h2><br>', $block);
		$class = preg_replace('/\s+/', '', $block);
		$replace = sprintf("</div><div class=\"%s tabcontent\">\n%s", $class, $search);
		$s = str_replace($search, $replace, $s);
	}
	return $s;
}

function formatLogLinks($baseUrl, $file){
    global $zipIndexes;
    $pageUrl = '&pageURL='.urlencode($baseUrl);

    $prod = false;
    if (isset($zipIndexes[$file]))
        $prod = true;
    if ($prod) {
        $baseLink = "{$_SERVER['SCRIPT_NAME']}?File=".urlencode($_GET['File'])."&Index=".$zipIndexes[$file];
    } else {
        $baseLink = "{$_SERVER['SCRIPT_NAME']}?Dir=".urlencode($_GET['Dir'])."&File=".urlencode($file);
    }

    if (preg_match('/\.html$/ims', $file)) {
        // html
        $s = "<a href=\"{$baseLink}&Format=html$pageUrl\">$file</a>";

        // no script
        $s .= " (<a href=\"{$baseLink}&NoScript=1$pageUrl\">no scripts</a>)";

        // screenshot
        $screenshotFilename = str_replace('.html', '-screenshot.png', $file);
        if ($prod) {
            $screenshotIndex = ArrayVal($zipIndexes, $screenshotFilename);
            if ($screenshotIndex) {
                $link = "?File=" . urlencode($_GET['File']) . "&Index=" . $screenshotIndex;
                $s .= " (<a href=\"{$link}&Format=html\">screenshot</a>)";
            }
        } else {
            if (isset($_GET['Dir']) and file_exists($_GET['Dir'] . '/' . $screenshotFilename)) {
                $link = "?Dir=" . urlencode($_GET['Dir']) . "&File=" . urlencode($screenshotFilename);
                $s .= " (<a href=\"{$link}&Format=image\">screenshot</a>)";
            }
        }

        // source
        $s .= " (<a href=\"{$baseLink}&Format=source$pageUrl\">source</a>)";

        // parsed
        $parsedDOMFilename = str_replace('.html', '-parsed.html', $file);
        if ($prod) {
            $parsedDOMIndex = ArrayVal($zipIndexes, $parsedDOMFilename);
            if ($parsedDOMIndex) {
                $link = "?File=" . urlencode($_GET['File']) . "&Index=" . $parsedDOMIndex;
                $s .= " (<a href=\"{$link}&Format=source\">XPath's DOM</a>)";
            }
        } else {
            if (isset($_GET['Dir']) and file_exists($_GET['Dir'] . '/' . $parsedDOMFilename)) {
                $link = "?Dir=" . urlencode($_GET['Dir']) . "&File=" . urlencode($parsedDOMFilename);
                $s .= " (<a href=\"{$link}&Format=source\">XPath's DOM</a>)";
            }
        }
    }
    elseif (preg_match('/\.png$/ims', $file)) {
        // image
        $s = " (<a href=\"{$baseLink}&Format=image$pageUrl\">$file</a>)";
    } else {
        // source
        $s = " (<a href=\"{$baseLink}&Format=source$pageUrl\">$file</a>)";
    }

    return $s;
}

function formatLogFileCallback($match){
	$link = formatLogLinks($match[2], $match[4]);
	return "{$match[1]}: {$match[2]}<br>
{$match[3]}<br>
saved $link";
}

function formatHttpLogFileCallback($match){
	return "saved ".formatLogLinks($match[2], $match[1]);
}

?>
