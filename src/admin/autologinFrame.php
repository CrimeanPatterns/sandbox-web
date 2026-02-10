<?php

require_once __DIR__."/../kernel/public.php";

$cache = getSymfonyContainer()->get("aw.shared_memcached");

$frameContents = $cache->get("autologin_" . $_GET['cacheKey']);
echo $frameContents;