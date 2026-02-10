<?php

// OUTDATED, moved to https://loyalty.awardwallet.com/pac
// OUTDATED, moved to https://loyalty.awardwallet.com/pac
// OUTDATED, moved to https://loyalty.awardwallet.com/pac
// OUTDATED, moved to https://loyalty.awardwallet.com/pac
// OUTDATED, moved to https://loyalty.awardwallet.com/pac
// OUTDATED, moved to https://loyalty.awardwallet.com/pac

require_once __DIR__ . '/Settings.php';

$default = 'DIRECT';

if (!empty($_GET['proxy'])) {
    if (!preg_match('#^[a-z\-\.\d]+:\d+$#ims', $_GET['proxy'])) {
        exit("invalid request");
    }
    $default = 'PROXY ' . $_GET['proxy'];
}

$cache = 'DIRECT';

if (!empty($_GET['cache'])) {
    $cache = $_GET['cache'];

    if (!preg_match('#^[a-z\-\.\d]+:\d+$#ims', $cache)) {
        exit("invalid request");
    }
    $cache = 'PROXY ' . $cache;
}

header('Content-Type: text/plain');

?>
function FindProxyForURL(url, host)
{
if(<?php echo implode("\n\t || ", array_map(function ($host) { return "shExpMatch(host, '{$host}')"; }, \AwardWallet\Engine\Settings::getExcludedHosts())); ?>)
return "SOCKS localhost:4443"; // non existent

if(shExpMatch(url, 'http:') && (shExpMatch(url, '*.jpg') || shExpMatch(url, '*.png') || shExpMatch(url, '*.jpeg') || shExpMatch(url, '*.gif') || shExpMatch(url, '*.css') || shExpMatch(url, '*.js') || shExpMatch(url, '*.woff') || shExpMatch(url, '*.ico')))
return '<?php echo $cache; ?>';

return "<?php echo $default; ?>";
}