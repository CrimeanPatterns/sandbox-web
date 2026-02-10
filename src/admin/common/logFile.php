<?php
require "../../kernel/public.php";
require __DIR__.'/../../manager/adminFunctions.php';

$file = $_GET['File'];
if(!preg_match('/^[a-z0-9\-\_]+\.(html|xml|png|txt|pdf)$/ims', $file, $matches))
    die("invalid file name");
$ext = $matches[1];
$format = ArrayVal($_GET, 'Format', 'html');

$dir = $_GET['Dir'];
assert(preg_match('/^\/var\/log\/www\/awardwallet\/tmp\/logs\/|^\/tmp\/parser-log-\w{16}/', $dir));

assert(file_exists($dir."/".$file));

if (isset($_GET['pageURL']) && $_GET['pageURL'] != "")
	$pageURL = $_GET['pageURL'];

if (!preg_match('/screenshot/', $file)) {
    echo "<div>".$dir."</div>";
    // screenshots
    $links = array();
    if (is_dir($dir)) {
        foreach (scandir($dir) as $name) {
            if (preg_match('/screenshot/', $name)) {
                $links[] = sprintf("<a href='?Dir=%s&Format=image&File=%s&NoScript=1'>%s</a>",
                    urlencode($dir),
                    $name,
                    preg_replace('/-screenshot.+/ims', '', $name)
                );
            }
        }
    }
    if (!isset($pageURL))
        echo "<h1>{$file}</h1>";
    if (sizeof($links) > 0) {
        echo "<div>steps: ".implode(" | ", $links)."</div>";
        echo '<hr>';
    }
}

$s = file_get_contents($dir."/".$file);

if($ext === 'pdf'){
	header('Content-type: application/pdf');
	ob_clean();
	echo $s;
	exit();
}

if (isset($pageURL) && !isset($_GET['Source'])) {
	$s = urlToAbsolute($s, $pageURL);
}

//header("Content-Security-Policy: cookie-scope 'none';");
$s = formatLogFile($s, $format, isset($_GET['NoScript']));
$s = formatClassesLogFile($s);
?>
<style type="text/css">.scroll::-webkit-scrollbar{-webkit-appearance:none;width:11px;height:11px;}.scroll::-webkit-scrollbar-thumb{border-radius:8px;border:2px solid white;background-color:rgba(0,0,0,.5);}</style>

<link rel="stylesheet" type="text/css" href="/assets/awardwalletnewdesign/css/base/logStyles.css">
<link rel="stylesheet" type="text/css" href="/assets/common/vendors/jquery.json-viewer/json-viewer/jquery.json-viewer.css">


<style type="text/css">
    .ParseItineraries span.miss, .AccountCheckResult span.miss {
        background-color: #b3ffff;
    }
    .ParseItineraries span.warn, .AccountCheckResult span.warn {
        background-color: #ffff66;
    }
    .ParseItineraries span.err, .AccountCheckResult span.err {
        background-color: #ffad99;
    }
    span.bracket_itineraries.bracket_odd {
        background-color: greenyellow;
    }
    span.bracket_itineraries.bracket_even {
        background-color: darkviolet;
    }
</style>
<!-- Table of log contents -->
<script src="/assets/common/vendors/jquery/dist/jquery.min.js"></script>
<script src="/assets/common/vendors/jquery.json-viewer/json-viewer/jquery.json-viewer.js"></script>
<script src="/assets/awardwalletnewdesign/js/lib/logScripts.js"></script>
<div id="awlog-contents"></div>
<?php if (!preg_match('/screenshot/', $file)): ?>
<hr>
<?php endif; ?>

<!-- /Table of log contents -->

<?php

if($ext === 'txt') {
    echo "<pre>";
    echo $s;
    echo "</pre>";
    exit();
}

echo $s;

