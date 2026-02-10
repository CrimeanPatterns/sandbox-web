#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';
error_reporting(E_ALL);

if ($argc !== 4) {
    echo("expected arguments: <from_folder> <to_folder> <memcached1:memcached2:..>\n");
    exit(1);
}

$fromFolder = $argv[1];
$toFolder = $argv[2];
/** @var \Memcached[] $servers */
$servers = array_map(function(string $host){
    $cache = new Memcached();
    $cache->addServer($host, 11211);
    return $cache;
}, explode(":", $argv[3]));

$logger = new \Monolog\Logger('app');
$folderSyncer = new \AwardWallet\Common\Parsing\Sync\FolderSyncer($logger);
$engineSyncer = new \AwardWallet\Common\Parsing\Sync\EngineSyncer($logger, $folderSyncer);

$engineSyncer->syncAll($fromFolder, $toFolder, true);

$time = time();
foreach ($servers as $server) {
    $server->set("aw_engine_update_date", $time);
}

$logger->info("done, updated " . count($servers) . " servers");