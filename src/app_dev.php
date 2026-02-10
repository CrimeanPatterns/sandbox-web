<?php

use Symfony\Component\HttpFoundation\Request;

if (strpos($_SERVER['DOCUMENT_URI'], "..") === false && strpos($_SERVER['DOCUMENT_URI'], ":") === false) { // overreacting, actually nginx will normalize path
    if (substr($_SERVER['DOCUMENT_URI'], -4) === '.php') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI']);
    }
    elseif (substr($_SERVER['DOCUMENT_URI'], -1) === '/') {
        $script = realpath($_SERVER['DOCUMENT_ROOT'] . $_SERVER['DOCUMENT_URI'] . "index.php");
    }
    if(isset($script) && file_exists($script) && strpos($script, $_SERVER['DOCUMENT_ROOT']) === 0){
        chdir(dirname($script));
        $_SERVER['SCRIPT_FILENAME'] = $script;
        $_SERVER['SCRIPT_NAME'] = substr($script, strlen($_SERVER['DOCUMENT_ROOT']));
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        require $script;
        exit();
    }
}

if ($_SERVER['DOCUMENT_URI'] === '/') {
    ?>
    <table width="100%">
        <tr style="text-align: left">
            <th>WEB</th>
            <th>RA</th>
        </tr>
        <tr>
            <td><a href="admin/debugParser.php">Debug Parser</a></td>
            <td><a href="admin/debugRewardAvailability.php">Debug Reward Availability</a></td>
        </tr>
        <tr>
            <td><a href="admin/debugProxy.php">Debug Proxy</a></td>
            <td><a href="admin/debugRaHotel.php">Debug Hotel Reward Availability</a></td>
        </tr>
        <tr>
            <td><a href="admin/debugConfirmation.php">Debug Confirmation</a></td>
            <td><a href="admin/debugRewardAvailabilityRegister.php">Debug RA Register Account</a></td>
        </tr>
        <tr>
            <td><a href="admin/debugAutologin.php">Debug Autologin</a></td>
            <td><a href="admin/debugKeepHotSession.php">Debug Keep Active Hot Session</a></td>
        </tr>
        <tr>
            <td><a href="admin/debugConfAutologin.php">Debug Conf No Autologin</a></td>
            <td></td>
        </tr>
        <tr>
            <td><a href="admin/debug-extension">Debug Browser Extension Parser</a></td>
            <td></td>
        </tr>
    </table>
    <?php
    exit();
}

require_once __DIR__.'/../app/kernel.php';

$kernel = getSymfonyKernel();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
